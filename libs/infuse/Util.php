<?php
/**
 * Base class for models
 * 
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
 
class Util
{
	static function toBytes($str)
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
	
	//from php.net user comments 
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
	
	static function encryptPassword( $password, $nonce = '' )
	{ // nonce currently not used
		return hash_hmac('sha512', $password . $nonce, Config::value( 'site', 'salt' ));
	}
	
	// lifted from php comments
	static function get_tz_options($selectedzone, $name = 'time_zone')
	{
	  $return = '<select name="' . $name . '">';
	  function timezonechoice($selectedzone) {
	    $all = timezone_identifiers_list();
	
	    $i = 0;
	    foreach($all AS $zone) {
	      $zone = explode('/',$zone);
	      $zonen[$i]['continent'] = isset($zone[0]) ? $zone[0] : '';
	      $zonen[$i]['city'] = isset($zone[1]) ? $zone[1] : '';
	      $zonen[$i]['subcity'] = isset($zone[2]) ? $zone[2] : '';
	      $i++;
	    }
	
	    asort($zonen);
	    $structure = '';
	    foreach($zonen AS $zone) {
	      extract($zone);
	      if($continent == 'Africa' || $continent == 'America' || $continent == 'Antarctica' || $continent == 'Arctic' || $continent == 'Asia' || $continent == 'Atlantic' || $continent == 'Australia' || $continent == 'Europe' || $continent == 'Indian' || $continent == 'Pacific') {
	        if(!isset($selectcontinent)) {
	          $structure .= '<optgroup label="'.$continent.'">'; // continent
	        } elseif($selectcontinent != $continent) {
	          $structure .= '</optgroup><optgroup label="'.$continent.'">'; // continent
	        }
	
	        if(isset($city) != ''){
	          if (!empty($subcity) != ''){
	            $city = $city . '/'. $subcity;
	          }
	          $structure .= "<option ".((($continent.'/'.$city)==$selectedzone)?'selected="selected "':'')." value=\"".($continent.'/'.$city)."\">".str_replace('_',' ',$city)."</option>"; //Timezone
	        } else {
	          if (!empty($subcity) != ''){
	            $city = $city . '/'. $subcity;
	          }
	          $structure .= "<option ".(($continent==$selectedzone)?'selected="selected "':'')." value=\"".$continent."\">".$continent."</option>"; //Timezone
	        }
	
	        $selectcontinent = $continent;
	      }
	    }
	    $structure .= '</optgroup>';
	    return $structure;
	  }
	  $return .= timezonechoice($selectedzone);
	  $return .= '</select>';
	  return $return;
	}
}