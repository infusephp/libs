<?php

/**
 * @package Infuse
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 1.0
 * @copyright 2013 Jared King
 * @license MIT
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
	associated documentation files (the "Software"), to deal in the Software without restriction,
	including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
	LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
	SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace infuse;

class ViewEngine Extends \Smarty
{
	var $base_template_dir;
	var $admin_template_dir;
	var $functions_dir;
	
	private static $engine;
	
	/*
	* Constructor
	*/
	function __construct( $options = array() )
	{
		parent::__construct();
		
		$this->error_reporting = 1;
		$this->base_template_dir = INFUSE_VIEWS_DIR;
		$this->template_dir = $this->base_template_dir . '/';
		$this->compile_dir = INFUSE_TEMP_DIR . '/smarty/';
		$this->cache_dir = INFUSE_TEMP_DIR . '/smarty/cache/';
		$this->functions_dir = INFUSE_BASE_DIR . '/libs/Smarty/functions';
        $this->assign('app_name', SITE_TITLE);
        
        if( isset( $options[ 'nocache' ] ) )
		{
			// turn off caching
	        $this->caching = 0;
        	$this->force_compile = true;
	        $this->compile_check = true;
		}
		else
		{
			// turn on caching
			//$this->setCaching( Smarty::CACHING_LIFETIME_CURRENT);
			//$this->compile_check = false;
        }
	}
	
	/**
	 * Compiles a LESS file and puts the output in APP_DIR/css
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $inputFile input LESS file
	 * @param sting $outputFileName output filename
	 */
	function compileLess( $inputFile, $outputFileName )
	{
        // create temp and output dirs
        if( !file_exists( INFUSE_TEMP_DIR . '/css' ) )
        	mkdir( INFUSE_TEMP_DIR . '/css' );
        if( !file_exists( INFUSE_APP_DIR . '/css' ) )
        	mkdir( INFUSE_APP_DIR . '/css' );

		$cacheFile = INFUSE_TEMP_DIR . '/css/' . $outputFileName . ".cache";
		
		$outputFile = INFUSE_APP_DIR . '/css/' . $outputFileName;
		
		// load the cache
		if( file_exists( $cacheFile ) ) {
			$cache = unserialize( file_get_contents( $cacheFile ) );
		} else {
			$cache = $inputFile;
		}
		
		$less = new \lessc;
		try
		{
			$newCache = $less->cachedCompile($cache);
			
			if( !is_array( $cache ) || $newCache[ 'updated' ] > $cache[ 'updated' ] ) {
				if( file_put_contents( $outputFile, $newCache[ 'compiled' ] ) )
					file_put_contents( $cacheFile, serialize( $newCache ) );
			}
		}
		catch( \Exception $ex )
		{
			echo "lessphp fatal error: " . $ex->getMessage();
		}
	}
	
	/**
	 * Compiles all of the javascript files in a directory in order by name.
	 * If the framework is in production mode then the javascript will be minified.
	 * NOTE: this function employs caching of file modified timesteamps to do as little work as possible
	 *
	 * @param string $jsDirectory path containing javascript to compile
	 * @param sting $outputFileName output filename
	 */
	function compileJs( $jsDirectory, $outputFileName )
	{
        // create temp and output dirs
        if( !file_exists( INFUSE_TEMP_DIR . '/js' ) )
        	mkdir( INFUSE_TEMP_DIR . '/js' );
        if( !file_exists( INFUSE_APP_DIR . '/js' ) )
        	mkdir( INFUSE_APP_DIR . '/js' );

		// NOTE js files get appended in order by filename
		// to change the order of js files, change the filename
		
		$cacheFile = INFUSE_TEMP_DIR . '/js/' . $outputFileName . ".cache";
		
		$outputFile = INFUSE_APP_DIR . '/js/' . $outputFileName;

		$cache = false;
		if( file_exists( $cacheFile ) ) {
			$cache = unserialize( file_get_contents( $cacheFile ) );
		}

		$jsFiles = glob( $jsDirectory . '/*.js' );

		$newCache = array(
			'md5' => $this->md5OfDir( $jsFiles ),
			'production' => Config::value( 'site', 'production-level' ) );

		if( !is_array( $cache ) || $newCache[ 'md5' ] != $cache[ 'md5' ] || $newCache[ 'production' ] != $cache[ 'production' ] ) {
			// concatenate the js for every file
			$js = '';
			foreach( $jsFiles as $file ) {
				$js .= file_get_contents( $file ) . "\n";
			}
			
			// minify js in production mode
			if( Config::value( 'site', 'production-level' ) ) {
				$js = \JSMin::minify( $js );
			}
			
			// write the js and cache to the output file
			if( file_put_contents( $outputFile, $js ) )
				file_put_contents( $cacheFile, serialize( $newCache ) );
		}
	}
	
	/**
	 * Passes an input array of data to the templating engine
	 *
	 * @param array $data key-value array
	 */
	function assignData( $data )
	{
		foreach( (array)$data as $key => $value )
			$this->assign( $key, $value );	
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