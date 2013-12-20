<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{
	public function testArrayValue()
	{
		$a = array(
			'test' => 2,
			'test2' => array(
				'3' => array(
					'4' => array(
						'asldfj'
					),
				),
				'5' => 1234
			)
		);

		$this->assertEquals( Util::array_value( $a, 'test' ), 2 );
		$this->assertEquals( Util::array_value( $a, 'test2.3.4' ), array( 'asldfj' ) );
		$this->assertEquals( Util::array_value( $a, 'test2.5' ), 1234 );

		$this->assertNull( Util::array_value( $a, 'nonexistent' ) );
		$this->assertNull( Util::array_value( $a, 'some.nonexistent.property' ) );
	}

	public function testArraySet()
	{
		$a = array();

		Util::array_set( $a, '1.2.3.4.5', 'test' );
		$expected = array( '1' => array( '2' => array( '3' => array( '4' => array( '5' => 'test' ) ) ) ) );
		$this->assertEquals( $expected, $a );

		Util::array_set( $a, 'test', 'ok?' );
		$expected[ 'test' ] = 'ok?';
		$this->assertEquals( $expected, $a );

		Util::array_set( $a, '1.2.3', 'test' );
		$expected[ '1' ][ '2' ][ '3' ] = 'test';
		$this->assertEquals( $expected, $a );
	}

	public function testEncryptPassword()
	{
		$password = 'most-secure-p4ssw0rd ever';

		$test = array(
			$password,
			Util::encrypt_password( $password ),
			Util::encrypt_password( $password, 'this is our salt' ),
			Util::encrypt_password( $password, 'this is our salt', 123456 ) );

		// test each combination once to ensure they are not equal
		for( $i = 0; $i < count( $test ); $i++ )
		{
			for( $j = $i + 1; $j < count( $test ); $j++ )
				$this->assertTrue( $test[ $i ] != $test[ $j ] );
		}
	}

	public function testGuid()
	{
		$guid1 = Util::guid();
		$guid2 = Util::guid();

		$this->assertEquals( 36, strlen( $guid1 ) );
		$this->assertEquals( 36, strlen( $guid2 ) );
		$this->assertTrue( $guid1 != $guid2 );

		$guid1 = Util::guid( false );
		$guid2 = Util::guid( false );

		$this->assertEquals( 32, strlen( $guid1 ) );
		$this->assertEquals( 32, strlen( $guid2 ) );
		$this->assertTrue( $guid1 != $guid2 );
	}

	public function testSeoify()
	{
		$this->assertEquals( 'some-test-string', Util::seoify( 'some test string' ) );
		$this->assertEquals( 'meh', Util::seoify( '*)#%*^&--meh' ) );
		$this->assertEquals( 'already-seoified-string', Util::seoify( 'already-seoified-string' ) );
	}

	public function testParseMetricStr()
	{
		$this->assertEquals( 1000000000000, Util::parse_metric_str( '1T' ) );
		$this->assertEquals( 50000000000, Util::parse_metric_str( '50G' ) );
		$this->assertEquals( 1400000, Util::parse_metric_str( '1.4M' ) );
		$this->assertEquals( 2000, Util::parse_metric_str( '2K' ) );

		$this->assertEquals( 1073741824, Util::parse_metric_str( '1GBytes', true ) );
	}

	public function testNumberAbbreviate()
	{
		$this->assertEquals( '12.3K', Util::number_abbreviate( 12345 ) );
		$this->assertEquals( '1M', Util::number_abbreviate( 1000000, 2 ) );

		$this->assertEquals( '-1234', Util::number_abbreviate( -1234, 2 ) );
		$this->assertEquals( '123', Util::number_abbreviate( 123, 3 ) );
		$this->assertEquals( '12.345K', Util::number_abbreviate( 12345, 3 ) );
		$this->assertEquals( '12.345M', Util::number_abbreviate( 12345000, 3 ) );
		$this->assertEquals( '1.23G', Util::number_abbreviate( 1234567890, 2 ) );
		$this->assertEquals( '1.23T', Util::number_abbreviate( 1234567890123, 2 ) );
	}

	public function testSetCookieFixDomain()
	{
		$name = 'session.name';
		$value = rand();
		$expire = time() + 3600;
		$path = '/test/path';
		$domain = 'www.example.com';
		$secure = true;
		$httponly = true;

		$cookieStr = Util::set_cookie_fix_domain(
			$name,
			$value,
			$expire,
			$path,
			$domain,
			$secure,
			$httponly,
			false );

		$expected = "Set-Cookie: $name=$value; path=$path; domain=.example.com; secure; HttpOnly";

		$this->assertEquals( $expected, $cookieStr );
	}

	public function testPrintPre()
	{
		$test = array( 'test' => array( 'who' => 'dat' ) );

		ob_start();
		Util::print_pre( $test );
		$output = ob_get_clean();

		$this->assertEquals( '<pre>' . print_r( $test, true ) . '</pre>', $output );
	}
}