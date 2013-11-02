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
	 * i.e. users.jared.address.city -> ['users']['jared']['address']['city']
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
	        $a = &$a[$piece];
	    
	    return $a = $value;
	}	
	
	/**
	 * Securely hashes a string, useful for passwords
	 *
	 * @param $string password
	 * @param int $nonce number used once
	 *
	 * @return string
	 */
	static function encryptPassword( $password, $nonce = '' )
	{
		return hash_hmac( 'sha512', $password . $nonce, Config::get( 'site', 'salt' ) );
	}	
	
	/**
	 * Generates a unique 32-digit GUID. i.e. 12345678-1234-5678-123456789012
	 *
	 * @return string
	 */
	static function guid()
	{
		if( function_exists( 'com_create_guid' ) )
			return trim( '{}', com_create_guid() );
		else
		{
			// mt_srand( (double)microtime() * 10000 ); optional for php 4.2.0+
			$charid = strtoupper( md5( uniqid( rand( ), true ) ) );
			// chr(45) = "-"
			$uuid = //chr(123)// "{"
					substr($charid, 0, 8).chr(45)
					.substr($charid, 8, 4).chr(45)
					.substr($charid,12, 4).chr(45)
					.substr($charid,16, 4).chr(45)
					.substr($charid,20,12);
					//.chr(125);// "}"
			return $uuid;
		}
	}
	
	/**
	 * Makes a string SEO compliant (numbers, digits, and dashes)
	 *
	 * @param string $string
	 * @param string $id id to be appended to end
	 */
	static function seoUrl( $string, $id = null )
	{
		$string = strtolower(stripslashes($string));
	 
		$string = preg_replace('/&.+?;/', '', $string); // kill HTML entities
		// kill anything that is not a letter, digit, space
		$string = preg_replace ("/[^a-zA-Z0-9 ]/", "", $string);		
		// Turn it to an array and strip common words by comparing against c.w. array
		$seo_slug_array = array_diff (explode(' ', $string), array());
		// Turn the sanitized array into a string
		$return = substr( join("-", $seo_slug_array), 0, 150 ) . ( ($id) ? '-' . $id : '' );
		// allow only single runs of dashes
		return strtolower(preg_replace('/--+/u', '-', $return));
	}
	
	/** 
	 * Converts a human friendly string (i.e. 1GB) into bytes
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	static function toBytes( $str )
	{
		// normalize and strip off any b's
		$str = str_replace( 'b', '', strtolower(trim($str)));
		// last letter
		$last = $str[strlen($str)-1];
		// get the value
		$val = substr( $str, 0, strlen($str) - 1 );
		switch($last) {
			case 't': $val *= 1024;
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}
	
	/**
	 * Formats a number with a set number of decimals and a metric suffix
	 * i.e. formatNumberAbbreviation( 12345, 2 ) -> 12.35K
	 *
	 * @param int $number
	 * @param int $decimals number of places after decimal
	 *
	 * @return string
	 */
	static function formatNumberAbbreviation($number, $decimals = 1)
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
	
	    foreach($abbrevs as $exponent => $abbrev)
	    {
	        if($number >= pow(10, $exponent))
	        {
	        	$remainder = $number % pow(10, $exponent) . ' ';
	        	$decimal = ($remainder > 0) ? round( round( $remainder, $decimals ) / pow(10, $exponent), $decimals ) : '';
	            return intval($number / pow(10, $exponent)) + $decimal . $abbrev;
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
	 * @param int $expires
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httponly
	 */
	static function set_cookie_fix_domain($Name, $Value = '', $Expires = 0, $Path = '', $Domain = '', $Secure = false, $HTTPOnly = false)
	{
		if (!empty($Domain))
		{
		  // Fix the domain to accept domains with and without 'www.'.
		  if (strtolower(substr($Domain, 0, 4)) == 'www.')  $Domain = substr($Domain, 4);
		  $Domain = '.' . $Domain;
	 
		  // Remove port information.
		  $Port = strpos($Domain, ':');
		  if ($Port !== false)  $Domain = substr($Domain, 0, $Port);
		}
	 
		header('Set-Cookie: ' . rawurlencode($Name) . '=' . rawurlencode($Value)
							  . (empty($Expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $Expires) . ' GMT')
							  . (empty($Path) ? '' : '; path=' . $Path)
			. (empty($Domain) ? '' : '; domain=' . $Domain)
			. (!$Secure ? '' : '; secure')
			. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}

	/**
	 * Useful for debugging
	 *
	 * @param mixed $element
	 */
	function print_pre( $element )
	{
		echo '<pre>' . print_r( $element, true ) . '</pre>';
	}	
}