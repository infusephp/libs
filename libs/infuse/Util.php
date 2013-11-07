<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.1
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;
 
class Util
{
	/**
	 * Looks up a key in an array. If the key follows dot-notation then a nested lookup will be performed.
	 * i.e. users.sherlock.address.lat -> ['users']['sherlock']['address']['lat']
	 *
	 * @param array $a array to be searched
	 * @param string $k key to search for
	 *
	 * @return mixed|null
	 */
	static function array_value( $a = array(), $k = '' )
	{
		$a = (array)$a;
		if( array_key_exists( $k, $a ) )
			return $a[ $k ];
		
	    $pieces = explode( '.', $k );
	    
	    // use dot notation to search a nested array
	    if( count( $pieces ) > 1 )
	    {
		    foreach( $pieces as $piece )
		    {
			    if( !is_array( $a ) || !array_key_exists( $piece, $a ) )
		        	// not found
		        	return null;
		        
		        $a = &$a[ $piece ];
		    }
		    
		    return $a;
		}
		
		return null;
	}
	
	/**
	 * Sets an element in an array using dot notation (i.e. fruit.apples.qty sets ['fruit']['apples']['qty']
	 *
	 * @param array $a
	 * @param string $key
	 * @param mixed $value
	 */
	static function array_set( &$a, $key, $value )
	{
	    $pieces = explode('.', $key);
	    
	    foreach( $pieces as $k => $piece )
	    {
	    	$a = &$a[$piece];
	    	if( !is_array( $a ) )
	    		$a = array();
	    }
	    
	    return $a = $value;
	}	
	
	/**
	 * Securely hashes a string, useful for passwords
	 *
	 * @param string $password
	 * @param string $salt
	 * @param int $nonce number used once
	 *
	 * @return string
	 */
	static function encrypt_password( $password, $salt = '', $nonce = '' )
	{
		return hash_hmac( 'sha512', $password . $nonce, $salt );
	}
	
	/**
	 * Generates a unique 32-digit GUID. i.e. 12345678-1234-5678-123456789012
	 *
	 * @param boolean $dashes whether or not to separate guid with dashes
	 * 
	 * @return string
	 */
	static function guid( $dashes = true )
	{
		if( function_exists( 'com_create_guid' ) )
			return trim( '{}', com_create_guid() );
		else
		{
			$charid = strtoupper( md5( uniqid( rand( ), true ) ) );

			$dash = $dashes ? '-' : '';

			$uuid = substr( $charid, 0, 8 ) . $dash .
					substr( $charid, 8, 4 ) . $dash .
					substr( $charid, 12, 4 ) . $dash .
					substr( $charid, 16, 4 ) . $dash .
					substr( $charid, 20, 12 );

			return $uuid;
		}
	}
	
	/**
	 * Makes a string SEO compliant (numbers, digits, and dashes only)
	 *
	 * @param string $string
	 * @param int $maxLength maximum length of result
	 * @param array $commonWords common words to remove
	 *
	 * @return string
	 */
	static function seoify( $string, $maxLength = 150, $commonWords = array() )
	{
		$string = strtolower( stripslashes( $string ) );
	 	// kill HTML entities
		$string = preg_replace( '/&.+?;/', '', $string );
		// kill anything that is not a letter, digit, space, dash
		$string = preg_replace( "/[^a-zA-Z0-9 -]/", "", $string );
		// turn it to an array and strip common words by comparing against c.w. array
		$seo_slug_array = array_diff( explode( ' ', $string ), $commonWords );
		// turn the sanitized array into a string of max length
		$return = substr( join( "-", $seo_slug_array ), 0, $maxLength );
		// allow only single runs of dashes
		$return = strtolower( preg_replace( '/--+/u', '-', $return ) );
		// first character cannot be '-'
		if( $return[ 0 ] == '-' )
			$return = substr_replace( $return, '', 0, 1 );

		return $return;
	}
	
	/** 
	 * Converts a human friendly metric string (i.e. 1G) into a number
	 *
	 * @param string $str
	 * @param string $use1024 when true, increment by 1,024 instead of 1,000
	 *
	 * @return number
	 */
	static function parse_metric_str( $str, $use1024 = false )
	{
		// normalize
		$str = strtolower( trim( $str ) );

		// strip off all letters and find suffix
		$i = strlen( $str ) - 1;
		while( $i >= 0 && !is_numeric( $str[ $i ] ) )
			$i--;

		// last letter
		$last = $str[ $i + 1 ];

		// get the number
		$val = substr( $str, 0, $i + 1 );

		// compute the value
		$thousand = ($use1024) ? 1024 : 1000;
		$hundred = ($use1024) ? 102.4 : 100;
		switch( $last )
		{
			case 't': $val *= $thousand;
			case 'g': $val *= $thousand;
			case 'm': $val *= $thousand;
			case 'k': $val *= 10;
			case 'h': $val *= $hundred;
		}

		return $val;
	}
	
	/**
	 * Formats a number with a set number of decimals and a metric suffix
	 * i.e. number_abbreviate( 12345, 2 ) -> 12.35K
	 *
	 * @param int $number
	 * @param int $decimals number of places after decimal
	 *
	 * @return string
	 */
	static function number_abbreviate( $number, $decimals = 1 )
	{
		if( $number == 0 )
			return "0";
			
		if( $number < 0 )
			return $number;
			
	    $abbrevs = array(
	    	24 => "Y",
	    	21 => "Z",
	    	18 => "E",
	    	15 => "P",
	    	12 => "T",
	    	9 => "G",
	    	6 => "M",
	    	3 => "K",
	    	0 => ""
	    );
	
	    foreach( $abbrevs as $exponent => $abbrev )
	    {
	        if( $number >= pow( 10, $exponent ) )
	        {
	        	$remainder = $number % pow( 10, $exponent ) . ' ';
	        	$decimal = ( $remainder > 0 ) ? round( round( $remainder, $decimals ) / pow( 10, $exponent ), $decimals ) : '';
	            return intval( $number / pow( 10, $exponent ) ) + $decimal . $abbrev;
	        }
	    }
	}
	
	/** 
	 * Sets the cookie with a properly formatted domain to fix older versions of IE dropping sessions
	 *
	 * from php.net user comments
	 * 
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httponly
	 * @param boolean $setHeader when true, sets the header, otherwise returns header string
	 *
	 * @return string when $setHeader = false
	 */
	static function set_cookie_fix_domain( $name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false, $setHeader = true )
	{
		if( !empty( $domain ) )
		{
		  // Fix the domain to accept domains with and without 'www.'
		  if( strtolower( substr( $domain, 0, 4 ) ) == 'www.' )
		  	$domain = substr( $domain, 4 );
		  $domain = '.' . $domain;
	 
		  // Remove port information
		  $port = strpos( $domain, ':' );
		  if ( $port !== false )
		  	$domain = substr( $domain, 0, $port );
		}
	 	
		$cookieStr = 'Set-Cookie: ' . rawurlencode( $name ) . '=' . rawurlencode( $value ) .
			( empty($expires) ? '' : '; expires=' . gmdate( 'D, d-M-Y H:i:s', $expires ) . ' GMT' ) .
			( empty($path) ? '' : '; path=' . $path ) .
			( empty($domain) ? '' : '; domain=' . $domain ) .
			( !$secure ? '' : '; secure' ) .
			( !$httponly ? '' : '; HttpOnly' );

		if( $setHeader )
			header( $cookieStr, false );
		else
			return $cookieStr;
	}

	/**
	 * Useful for debugging
	 *
	 * @param mixed $element
	 */
	static function print_pre( $element )
	{
		echo '<pre>' . print_r( $element, true ) . '</pre>';
	}

	/**
	 * @deprecated
	 */
	static function encryptPassword( $password, $nonce )
	{
		return self::encrypt_password( $password, Config::get( 'site.salt' ), $nonce );
	}

	/**
	 * @deprecated
	 */
	static function seoURL( $string, $id = null )
	{
		return self::seoify( $string . (($id)?'-'.$id:''));
	}

	/**
	 * @deprecated
	 */
	static function toBytes( $str )
	{
		return self::parse_metric_str( $str, true );
	}
	
	/**
	 * @deprecated
	 */
	static function formatNumberAbbreviation( $number, $decimals = 1 )
	{
		return self::number_abbreviate( $number, $decimals );
	}	
}