<?php
/**
 * This class provides validation functions for commonly used fields, such as names, e-mail addresses, IP addresses, etc.
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

use \infuse\ErrorStack as ErrorStack;
use \infuse\Modules as Modules;
use \infuse\models\User as User;
use \infuse\Messages as Messages;
use \infuse\Util as Util;

class Validate
{
	/**
	* States (USA only)
	* @staticvar array
	*/
	static $states = array('AL'=>"Alabama",  'AK'=>"Alaska",  'AZ'=>"Arizona",  'AR'=>"Arkansas",  'CA'=>"California",  'CO'=>"Colorado",  'CT'=>"Connecticut",  'DE'=>"Delaware",  'DC'=>"District Of Columbia",  'FL'=>"Florida",  'GA'=>"Georgia",  'HI'=>"Hawaii",  'ID'=>"Idaho",  'IL'=>"Illinois",  'IN'=>"Indiana",  'IA'=>"Iowa",  'KS'=>"Kansas",  'KY'=>"Kentucky",  'LA'=>"Louisiana",  'ME'=>"Maine",  'MD'=>"Maryland",  'MA'=>"Massachusetts",  'MI'=>"Michigan",  'MN'=>"Minnesota",  'MS'=>"Mississippi",  'MO'=>"Missouri",  'MT'=>"Montana",  'NE'=>"Nebraska",  'NV'=>"Nevada",  'NH'=>"New Hampshire",  'NJ'=>"New Jersey",  'NM'=>"New Mexico",  'NY'=>"New York",  'NC'=>"North Carolina",  'ND'=>"North Dakota",  'OH'=>"Ohio",  'OK'=>"Oklahoma",  'OR'=>"Oregon",  'PA'=>"Pennsylvania",  'RI'=>"Rhode Island",  'SC'=>"South Carolina",  'SD'=>"South Dakota",  'TN'=>"Tennessee",  'TX'=>"Texas",  'UT'=>"Utah",  'VT'=>"Vermont",  'VA'=>"Virginia",  'WA'=>"Washington",  'WV'=>"West Virginia",  'WI'=>"Wisconsin",  'WY'=>"Wyoming");
	
	/**
	* Validates an e-mail address
	*
	* @param string $email e-mail address
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function email( &$email, $parameters = array() )
	{
		$email = trim(strtolower($email));

		if( filter_var($email, FILTER_VALIDATE_EMAIL) === false )
			return false;

		if( !val( $parameters, 'skipBanCheck' ) )
		{
			Modules::load('bans');
			if( \infuse\models\Ban::isBanned( $email, BAN_TYPE_EMAIL ) )
			{
				ErrorStack::add( 'email_address_banned' );
				return false;
			}
		}

		return true;
	}
	
	/**
	* Validates a user name
	*
	* @param string $username user name
	* @param array $parameters parameters for validation
	*
	* @param boolean success
	*/
	static function username( &$username, $parameters = array() )
	{
		if (!(strlen($username) >= 1) || !preg_match( '/^[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*$/', $username ) )
			return false;

		if( !val( $parameters, 'skipBanCheck' ) )
		{
			Modules::load('bans');
			if( \infuse\models\Ban::isBanned( $username, BAN_TYPE_USERNAME ) )
			{
				ErrorStack::add( 'user_name_banned' );
				return false;
			}
		}

		return true;
	}

	/**
	* Validates two passwords making sure they are both equal and valid
	*
	* @param array|string password array(password 1, password 2) or passwordd
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function password( &$password, $parameters = array() )
	{
		$password1 = $password2 = '';
		if( is_array( $password ) )
		{
			$password1 = ( isset( $password[ 0 ] ) ) ? $password[ 0 ] : '';
			$password2 = ( isset( $password[ 1 ] ) ) ? $password[ 1 ] : '';
		}
		else
		{
			$password1 = $password;
			$password2 = $password;
		}
		
		$min_pass_length = Modules::info( 'users' )[ 'minimum-password-length' ];

		// Check if password is at least N characters long.
		if( strlen( $password1 ) >= $min_pass_length )
		{
			 // Check if passwords match.
			if( $password1 != $password2 )
			{
				ErrorStack::add( 'passwords_not_matching' );
				return false;
			}
		}
		else
			return false;
		
		// encrypt password
		$password = Util::encryptPassword( $password1 );

		return true;
	}
	
	/**
	* Validates a boolean value which may be in string form
	*
	* @param string $val value
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function boolean_( &$val, $parameters = array() )
	{
		$val = isset($val) && ( $val === true || $val === 1 || $val === '1' || $val == 'y' || $val == 'yes' || $val == 'on' );
		return true;
	}
	
	/**
	* Validates a group ID
	*
	* @param int $group_id group ID
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function group( &$group_id, $parameters = array() )
	{
		// check if the group ID exists
		if( !is_numeric( $group_id ) )//|| !Groups::exists( $group_id ) )
		{
			// ERROR
			return false;
		}
		
		// cannot start out as an admin
		if( $group_id == ADMIN && User::currentUser()->group()->id() != ADMIN )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}

		// can the user change groups?
		if( !isset( $parameters[ 'skipPermissionsCheck' ] ) &&
			isset( $parameters[ 'model' ] ) &&
			$parameters['model']->group()->id() != $group_id &&
			User::currentUser()->group()->id() != ADMIN )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}
		
		return true;
	}

	/**
	* Validates a first name
	*
	* @param string $fname first name
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function firstName( &$fname, $parameters = array() )
	{
		if( !isset( $fname ) || strlen($fname) < 2 )
			return false;
	
		return true;
	}
	
	/**
	* Validates a last name
	*
	* @param string $lname last name
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function lastName( &$lname, $parameters = array() )
	{
		// not doing anything here for now
		return true;
	}
	
	/**
	* Validates a company
	*
	* @param string $company company
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function company( &$company, $parameters = array() )
	{
		// not doing anything here for now
		return true;
	}
	
	/**
	* Validates an address
	*
	* @param string $address
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function address( &$address, $parameters = array() )
	{
		if (!(strlen($address) >= 5) )
		{
			// ERROR
			return false;
		}
	
		return true;
	}
	
	/**
	* Validates a zip code
	*
	* @param string $zip zip code
	* @param array $parameters parameters for validation
	*
	* @return booelan success
	*/
	static function zip( &$zip, $parameters = array() )
	{
		$validated = true;
		if (!is_numeric($zip))
		{
			if (!preg_match('/^[0-9]{5}([- ]?[0-9]{4})?$/', $zip))
				$validated = false;
		}
		else
		{
			if (!is_numeric($zip) || !(strlen($zip) == 5 || strlen($zip) == 9)) // Check if zip code is a 5 or 9 character number.
				$validated = false;
		}
		

		if (!$validated)
		{
			displayError("invalid_zip_code",'zip_code',NULL,'module');
			return false;
		}
		
		$zip = preg_replace("/[[:^digit:]]/", '', $zip);
	
		return true;
	}
	
	/**
	* Validates a city
	* @param string $city city
	* @return boolean success
	*/
	static function city( &$city, $parameters = array() )
	{
		if (!(strlen($city) >= 2))
		{
			// ERROR
			return false;
		}
		elseif (!preg_match("/^[A-Za-z ]*$/", $city))
		{
			// ERROR
			return false;
		}
		
		return true;
	}
	
	/**
	* Validates a state
	*
	* @param string $state state
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function state( &$state, $parameters = array() )
	{
		if (!array_key_exists($state, self::$states))
			return false;
	
		return true;
	}
	
	/**
	* Validates a country
	*
	* @param string $country country
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function country( &$country, $parameters = array() )
	{
		if (strlen($country) < 2)
		{
			// ERROR
			return false;
		}
		elseif (!preg_match("/^[A-Za-z]*$/", $country))
		{
			// ERROR
			return false;
		}
	
		return true;
	}
	
	/**
	* Validates a phone number
	* @param int $phone phone number
	* @param boolean $null_allowed true if the phone number is allowed to be empty
	* @param string $type phone number type
	* @return int updated phone number
	*/
	static function phone( &$phone, $parameters = array() )
	{	
		if ($phone == '') return true;
		
		$phone = preg_replace("/[[:^digit:]]/", '', $phone);
		
		if (strlen($phone) < 7 || !is_numeric($phone)) {
	
			switch (val( $parameters, 'type' ))
			{
			case "work":		$field = "workphone";		break;
			case "home":		$field = "homephone";		break;
			case "cell":		$field = "cellphone";		break;
			case "phone":		$field = "phone";			break;
			case "fax":			$field = "fax";				break;
			default:			$field = "error";			break;
			}
	
			// ERROR
			return false;
		}

		return true;
	}

	/**
	* Validates a time zone
	*
	* @param string $time_zone time zone
	* @param array $parameters parameters for validation
	*
	* @return boolean success
	*/
	static function timeZone( &$time_zone, $parameters = array() )
	{	
		// thanks to http://stackoverflow.com/questions/5816960/how-to-check-is-timezone-identifier-valid-from-code
		$valid = array();
		$tza = timezone_abbreviations_list();
		foreach ($tza as $zone)
			foreach ($zone as $item)
				$valid[$item['timezone_id']] = true;
		unset($valid['']);
		return !!$valid[$time_zone];
	}
	
	/**
	* Validates then retrieves the full 9-digit zip code for an address
	* @param string $address address
	* @param string $address2 address 2
	* @param string $city city
	* @param string $state state
	* @param string $zip zip code
	* @return int zip code
	*/
	static function getZipCode( $address, $address2, $city, $state, $zip )
	{
		if (strlen( $zip ) == 5 || empty( $zip ) ) // Attempt to find the full 9-digit zip code
		{	
			$url = 'http://zip4.usps.com/zip4/zcl_0_results.jsp';
			$fields = array(
			 'visited'=>urlencode("1"),
			 'pagenumber'=>urlencode("0"),
			 'firmname'=>urlencode(""),
			 'urbanization'=>urlencode(""),
			 'address1'=>urlencode($address),
			 'address2'=>urlencode($address2),
			 'city'=>urlencode($city),
			 'state'=>urlencode($state),
			 'zip5'=>urlencode(""),
			 'submit'=>urlencode("Find ZIP Code")
			 );
			
			//url-ify the data for the POST
			$fields_string = null;
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string,'&');
			
			//open connection
			$ch = curl_init();
			
			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			
			//execute post
			$result = curl_exec($ch);
			
			//close connection
			curl_close($ch);
			
			$texttofind = strtoupper( $city )."&nbsp;".strtoupper( $state )."&nbsp;&nbsp;";
			$start = strpos($result,$texttofind);
			$result = substr($result,$start+strlen($texttofind),10);
			if (!preg_match('/^[0-9]{5}([- ]?[0-9]{4})?$/', $result))
				$result = "00000";
				
			if (substr($result, 0, 5) == $zip || empty( $zip ) )
				return $result;
			else
				return $zip;
		}
		else if( strlen( $zip ) == 9 )
			return substr_replace( $zip, '-', 5, 0);
		else
			return $zip;	
	}
}