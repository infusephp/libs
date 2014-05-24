<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class ViewEngine
{		
	private static $defaultOptions = [
		'engine' => 'smarty',
		'viewsDir' => 'views',
		'compileDir' => 'temp/smarty',
		'cacheDir' => 'temp/smarty/cache',
		'assetVersionsFile' => 'temp/asset_version_numbers.json',
		'assetsBaseUrl' => ''
	];
	
	private static $extensionMap = [
		'smarty' => '.tpl',
		'php' => '.php'
	];
	
	private static $engine;
	
	private $type;
	private $viewsDir;
	private $compileDir;
	private $cacheDir;
	private $assetVersionsFile;
	private $assetVersionNumbers;
	private $assetsBaseUrl;
	
	private $smarty;

	private $data;
	
	/**
	 * Configures the engine to use the specified settings. This overwrites any previous instances of the engine.
	 *
	 * @param array $options
	 */
	static function configure( array $options )
	{
		self::$engine = new self( $options );
	}

	/**
	 * Creates a new instance
	 *
	 * @param array $options
	 */
	function __construct( array $options = [] )
	{
		$options = array_replace( static::$defaultOptions, $options );
		
		$this->type = $options[ 'engine' ];
		$this->viewsDir = $options[ 'viewsDir' ];
		$this->compileDir = $options[ 'compileDir' ];
		$this->cacheDir = $options[ 'cacheDir' ];
		$this->assetVersionsFile = $options[ 'assetVersionsFile' ];
		$this->assetsBaseUrl = $options[ 'assetsBaseUrl' ];
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
			$versionNumbers = [];
			if( file_exists( $this->assetVersionsFile ) )
				$versionNumbers = json_decode( file_get_contents( $this->assetVersionsFile ), true );
			$this->assetVersionNumbers = $versionNumbers;
		}
		
		$v = Util::array_value( $this->assetVersionNumbers, $location );
		return $this->assetsBaseUrl . $location . (($v)?'?v=' . $v : '') ;
	}

	/**
	 * Renders a template with optional parameters
	 *
	 * @param string $template
	 * @param array $parameters
	 *
	 * @return string rendered template
	 */
	function render( $template, $parameters = [] )
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
	 * Passes a key-value map of variables to the template
	 *
	 * @param array $data key-value array
	 */
	function assignData( array $data )
	{
		foreach( $data as $key => $value )
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
}