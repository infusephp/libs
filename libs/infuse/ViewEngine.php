<?php


namespace infuse;

class ViewEngine
{		
	private static $defaultOptions = array(
		'engine' => 'smarty',
		'viewsDir' => 'views',
		'compileDir' => 'temp/smarty',
		'cacheDir' => 'temp/smarty/cache'
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
	
	private $smarty;

	private $data;
	
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
	 */
	function compileLess( $input, $cacheFile, $output )
	{
		// load the cache
		if( file_exists( $cacheFile ) ) {
			$cache = unserialize( file_get_contents( $cacheFile ) );
		} else {
			$cache = $input;
		}
		
		$less = new \lessc;
		try
		{
			$newCache = $less->cachedCompile( $cache );
			
			if( !is_array( $cache ) || $newCache[ 'updated' ] > $cache[ 'updated' ] ) {
				if( file_put_contents( $output, $newCache[ 'compiled' ] ) )
					file_put_contents( $cacheFile, serialize( $newCache ) );
			}
		}
		catch( \Exception $ex )
		{
			Logger::error( "lessphp fatal error: " . $ex->getMessage() );
		}
	}
	
	/**
	 * Compiles all of the javascript files in a directory in order by name.
	 * If the framework is in production mode then the javascript will be minified.
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $dir path containing javascript to compile
	 * @param string $cacheFile path containing cache
	 * @param sting $output output filename
	 */
	function compileJs( $dir, $cacheFile, $output )
	{
		// NOTE js files get appended in order by filename
		// to change the order of js files, change the filename
		$cache = false;
		if( file_exists( $cacheFile ) )
			$cache = unserialize( file_get_contents( $cacheFile ) );

		$jsFiles = glob( $dir . '/*.js' );
		
		$newCache = array(
			'md5' => $this->md5OfDir( $jsFiles ),
			'production' => Config::get( 'site', 'production-level' ) );
		
		if( !is_array( $cache ) || $newCache[ 'md5' ] != $cache[ 'md5' ] || $newCache[ 'production' ] != $cache[ 'production' ] || !file_exists( $output ) )
		{
			// concatenate the js for every file
			$js = '';
			foreach( $jsFiles as $file )
				$js .= file_get_contents( $file ) . "\n";
			
			// minify js in production mode
			if( Config::get( 'site', 'production-level' ) )
				$js = \JSMin::minify( $js );
			
			// write the js and cache to the output file
			if( file_put_contents( $output, $js ) )
				file_put_contents( $cacheFile, serialize( $newCache ) );
		}
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
	 * Configures the engine to use the specified settings. This overwrites any previous instances of the engine.
	 *
	 * @param array $options
	 */
	static function configure( $options )
	{
		self::$engine = new self( $options );
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