<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.19
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class ViewEngine
{		
	private static $defaultOptions = array(
		'engine' => 'smarty',
		'viewsDir' => 'views',
		'compileDir' => 'temp/smarty',
		'cacheDir' => 'temp/smarty/cache',
		'assetVersionsFile' => 'temp/asset_version_numbers.json'
	);
	
	private static $extensionMap = array(
		'smarty' => '.tpl',
		'php' => '.php'
	);
	
	private static $engine;
	
	private $type;
	private $viewsDir;
	private $compileDir;
	private $cacheDir;
	private $assetVersionsFile;
	private $assetVersionNumbers;
	
	private $smarty;

	private $data;
	
	/**
	 * Configures the engine to use the specified settings. This overwrites any previous instances of the engine.
	 *
	 * @param array $options
	 */
	static function configure( $options )
	{
		self::$engine = new self( $options );
	}

	/**
	 * Creates a new instance
	 *
	 * @param array $options
	 */
	function __construct( $options = array() )
	{
		$options = array_replace( static::$defaultOptions, $options );
		
		$this->type = $options[ 'engine' ];
		$this->viewsDir = $options[ 'viewsDir' ];
		$this->compileDir = $options[ 'compileDir' ];
		$this->cacheDir = $options[ 'cacheDir' ];
		$this->assetVersionsFile = $options[ 'assetVersionsFile' ];
	}
	
	/**
	 * Generates the complete URL for a given asset with a version number
	 * if available. Requires config assets.base_url to be set.
	 * i.e. asset_url( '/img/logo.png' ) -> http://cdn.example.com/img/logo.png?v=2d82lf9sd8f
	 *
	 * @param string $location path portion of url (everything after host name beginning with /)
	 *
	 * @return string
	 */
	function asset_url( $location )
	{
		// load asset version numbers (if they exist)
		if( !$this->assetVersionNumbers )
		{
			$versionNumbers = array();
			if( file_exists( $this->assetVersionsFile ) )
				$versionNumbers = json_decode( file_get_contents( $this->assetVersionsFile ), true );
			$this->assetVersionNumbers = $versionNumbers;
		}
		
		$v = Util::array_value( $this->assetVersionNumbers, $location );
		return Config::get( 'assets.base_url' ) . $location . (($v)?'?v=' . $v : '') ;
	}

	/**
	 * Renders a template with optional parameters
	 *
	 * @param string $template
	 * @param array $parameters
	 *
	 * @return string rendered template
	 */
	function render( $template, $parameters = array() )
	{
		$extension = self::$extensionMap[ $this->type ];
		
		// add extension if left off
		$len = strlen( $extension );
		if( substr( $template, -$len, $len ) != $extension )
			$template .= $extension;
			
		$this->assignData( $parameters );			
		
		if( $this->type == 'smarty' )
		{
			return $this->smarty()->fetch( $template );
		}
		else if( $this->type == 'php' )
		{
			extract( $this->data );
			
			ob_start();
			include $this->viewsDir . '/' . $template;
			$theTemplateRenderedString = ob_get_contents();
			ob_end_clean();
			
			return $theTemplateRenderedString;
		}
	}
	
	/**
	 * Compiles a LESS file and puts the output in APP_DIR/css
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $input LESS input filename
	 * @param string $cacheFile cache file name
	 * @param sting $output CSS output filename
	 *
	 * @return boolean result
	 */
	function compileLess( $input, $cacheFile, $output )
	{
		try
		{
			// load the cache
			if( file_exists( $cacheFile ) ) {
				$cache = unserialize( file_get_contents( $cacheFile ) );
			} else {
				$cache = $input;
			}
			
			$less = new \lessc;

			$newCache = $less->cachedCompile( $cache );
			
			if( !is_array( $cache ) || $newCache[ 'updated' ] > $cache[ 'updated' ] ) {
				if( file_put_contents( $output, $newCache[ 'compiled' ] ) )
					file_put_contents( $cacheFile, serialize( $newCache ) );
			}

			return true;
		}
		catch( \Exception $e )
		{
			Logger::error( $e );
		}

		return false;
	}
	
	/**
	 * Compiles all of the javascript files in a directory in order by name.
	 * If the framework is in production mode then the javascript will be minified.
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $dir path containing javascript to compile
	 * @param string $cacheFile path containing cache
	 * @param sting $output output filename
	 * @param boolean $productionLevel performs extra minification
	 *
	 * @return boolean result
	 */
	function compileJs( $dir, $cacheFile, $output, $productionLevel = false )
	{
		try
		{
			// NOTE js files get appended in order by filename
			// to change the order of js files, change the filename
			$cache = false;
			if( file_exists( $cacheFile ) )
				$cache = unserialize( file_get_contents( $cacheFile ) );

			// collect all js files in sub directories
			$jsFiles = glob( $dir . '/*.js' );

			while( $dirs = glob( $dir . '/*', GLOB_ONLYDIR ) )
			{
				$dir .= '/*';
				$jsFiles = array_merge( $jsFiles, glob( $dir . '/*.js' ) );
			}

			// sort js files by name
			sort( $jsFiles );
			
			$newCache = array(
				'md5' => $this->md5OfDir( $jsFiles ),
				'production' => $productionLevel );
			
			if( !is_array( $cache ) || $newCache[ 'md5' ] != $cache[ 'md5' ] || $newCache[ 'production' ] != $cache[ 'production' ] || !file_exists( $output ) )
			{
				// concatenate the js for every file
				$js = '';
				foreach( $jsFiles as $file )
					$js .= file_get_contents( $file ) . "\n";
				
				// minify js in production mode
				if( $productionLevel )
					$js = \JSMin::minify( $js );
				
				// write the js and cache to the output file
				if( file_put_contents( $output, $js ) )
					file_put_contents( $cacheFile, serialize( $newCache ) );
			}

			return true;
		}
		catch( \Exception $e )
		{
			Logger::error( $e );
		}

		return false;
	}
	
	/**
	 * Inlines all of the angularJS templates in a directory in order by name.
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $dir path containing angular html templates to compile
	 * @param string $cacheFile path containing cache
	 * @param sting $output output filename
	 *
	 * @return boolean result
	 */
	function compileAngularTemplates( $dir, $cacheFile, $output )
	{
		try
		{
			// NOTE js files get appended in order by filename
			// to change the order of js files, change the filename
			$cache = false;
			if( file_exists( $cacheFile ) )
				$cache = unserialize( file_get_contents( $cacheFile ) );

			// collect all angular template file names in sub directories
			$angFiles = glob( $dir . '/*.html' );

			while( $dirs = glob( $dir . '/*', GLOB_ONLYDIR ) )
			{
				$dir .= '/*';
				$angFiles = array_merge( $angFiles, glob( $dir . '/*.html' ) );
			}

			// sort js files by name
			sort( $angFiles );
			
			$newCache = array(
				'md5' => $this->md5OfDir( $angFiles ) );
			
			if( !is_array( $cache ) || $newCache[ 'md5' ] != $cache[ 'md5' ] || !file_exists( $output ) )
			{
				// concatenate the html for every file
				$templates = '';
				foreach( $angFiles as $file )
				{
					$templates .= '<script type="text/ng-template" id="' . $file . '">';
					$templates .= file_get_contents( $file ) . "\n";
					$templates .= '</script>';			
				}
				
				// write the inlined templates and cache to the output file
				if( file_put_contents( $output, $templates ) )
					file_put_contents( $cacheFile, serialize( $newCache ) );
			}

			return true;
		}
		catch( \Exception $e )
		{
			Logger::error( $e );
		}

		return false;
	}

	/**
	 * Creates a map of version numbers for each file in a directory.
	 * The output is stored as JSON in a given file. This is useful
	 * for versioning static assets for use with CDNs. Version numbers
	 * are computed as the md5 of the file contents.
	 *
	 * @param array $dirs collection of directories to version
	 * @param array $cacheExts caching extensions
	 * @param string $output output filename for resulting JSON
	 * @param string $base base of resulting key for each path
	 * @param string $stripPrefix prefix to strip from file keys
	 * 
	 * @return boolean success
	 */
	function computeVersionNumbers( array $dirs, array $cacheExts, $output, $base = '', $stripPrefix = '' )
	{
		try
		{
			$versionNums = array();

			foreach( $dirs as $startDir )
			{
				$dir_iterator = new \RecursiveDirectoryIterator( $startDir );
				$iterator = new \RecursiveIteratorIterator( $dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST );

				foreach( $iterator as $file )
				{
					if( $file->isFile() && in_array( $file->getExtension(), $cacheExts ) )
						$versionNums[ $base . str_replace( $stripPrefix, '', $file->getPathname() ) ] = md5( file_get_contents( $file->getRealPath() ) );
				}
			}

			file_put_contents( $output, json_encode( $versionNums ) );

			return true;
		}
		catch( \Exception $e )
		{
			Logger::error( $e );
		}

		return false;
	}

	/**
	 * Passes a key-value map of variables to the template
	 *
	 * @param array $data key-value array
	 */
	function assignData( $data )
	{
		foreach( (array)$data as $key => $value )
			$this->assign( $key, $value );	
	}
	
	/**
	 * Sets a variable to be passed to the template
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function assign( $key, $value )
	{
		if( $this->type == 'smarty' )
			$this->smarty()->assign( $key, $value );
		else if( $this->type == 'php' )
			$this->data[ $key ] = $value;
	}
	
	/**
	 * Returns the view engine class
	 *
	 * @return ViewEngine view engine class
	 */
	static function engine()
	{
		if( !self::$engine )
			self::$engine = new self();
		
		return self::$engine;
	}
	
	/**
	 * Gets (and creates) the Smarty instance used by this clsas
	 *
	 * @return Smarty
	 */
	function smarty()
	{
		if( !$this->smarty )
		{
			$this->smarty = new \Smarty;
			
			$this->smarty->muteExpectedErrors();
			$this->smarty->setTemplateDir( $this->viewsDir )
						 ->setCompileDir( $this->compileDir )
						 ->setCacheDir( $this->cacheDir );
		}
		
		return $this->smarty;
	}
	
	/////////////////////////////////
	// PRIVATE FUNCTIONS
	/////////////////////////////////
		
	private function md5OfDir( $files )
	{
		$ret = '';
		foreach( $files as $filename )
		{
			if( $filename != '.' && $filename != '..' )
			{
				$filetime = filemtime( $filename );
				if( $filetime === false )
					return false;
				$ret .= date( "YmdHis", $filetime ) . basename( $filename );
			}
		}
		
		return md5($ret);
	}	
}