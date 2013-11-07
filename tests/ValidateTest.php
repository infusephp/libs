<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.3
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Config;
use infuse\Util;
use infuse\Validate;

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

class ValidateTest extends \PHPUnit_Framework_TestCase
{
	public function testValidatePassword()
	{
		Config::set( 'site.salt', 'saltvalue' );

		$password = 'testpassword';
		$this->assertTrue( Validate::is( $password, 'password:8' ) );
		$this->assertEquals( Util::encrypt_password( 'testpassword' , Config::get( 'site.salt' ) ), $password );

		$invalid = '...';
		$this->assertFalse( Validate::is( $invalid, 'password:8' ) );
	}

	public function testValidateMatching()
	{
		$match = array( 'test', 'test' );
		$this->assertTrue( Validate::is( $match, 'matching' ) );
		$this->assertEquals( 'test', $match );

		$match = array( 'test', 'test', 'test', 'test' );
		$this->assertTrue( Validate::is( $match, 'matching' ) );
		$this->assertEquals( 'test', $match );

		$notmatching = array( 'test', 'nope' );
		$this->assertFalse( Validate::is( $notmatching, 'matching' ) );
		$this->assertEquals( array( 'test', 'nope' ), $notmatching );		
	}
}