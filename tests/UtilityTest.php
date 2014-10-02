<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Utility as U;

class UtilityTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayValue()
    {
        $a = [
            'test' => 2,
            'test2' => [
                '3' => [
                    '4' => [
                        'asldfj'
                    ],
                ],
                '5' => 1234
            ]
        ];

        $this->assertEquals( U::array_value( $a, 'test' ), 2 );
        $this->assertEquals( U::array_value( $a, 'test2.3.4' ), [ 'asldfj' ] );
        $this->assertEquals( U::array_value( $a, 'test2.5' ), 1234 );

        $this->assertNull( U::array_value( $a, 'nonexistent' ) );
        $this->assertNull( U::array_value( $a, 'some.nonexistent.property' ) );
    }

    public function testArraySet()
    {
        $a = [];

        U::array_set( $a, '1.2.3.4.5', 'test' );
        $expected = [ '1' => [ '2' => [ '3' => [ '4' => [ '5' => 'test' ] ] ] ] ];
        $this->assertEquals( $expected, $a );

        U::array_set( $a, 'test', 'ok?' );
        $expected[ 'test' ] = 'ok?';
        $this->assertEquals( $expected, $a );

        U::array_set( $a, '1.2.3', 'test' );
        $expected[ '1' ][ '2' ][ '3' ] = 'test';
        $this->assertEquals( $expected, $a );
    }

    public function testArrayDot()
    {
        $a = [ '1' => [ '2' => [ '3' => [ '4' => [ '5' => 'test' ] ] ] ] ];
        $expected = [ '1.2.3.4.5' => 'test' ];

        $this->assertEquals( $expected, U::array_dot( $a ) );

        $a = [
            'fruit' => [
                'apples' => [
                    'sold' => 100,
                    'remaining' => 100,
                    'percent' => 0.5
                ],
                'oranges' => [
                    'remaining' => 0
                ]
            ],
            'test' => true
        ];
        $expected = [
            'fruit.apples.sold' => 100,
            'fruit.apples.remaining' => 100,
            'fruit.apples.percent' => 0.5,
            'fruit.oranges.remaining' => 0,
            'test' => true
        ];

        $this->assertEquals( $expected, U::array_dot( $a ) );
    }

    public function testEncryptPassword()
    {
        $password = 'most-secure-p4ssw0rd ever';

        $test = [
            $password,
            U::encrypt_password( $password, 'salt should not be empty' ),
            U::encrypt_password( $password, 'this is our salt' ),
            U::encrypt_password( $password, 'this is our salt', 123456 ) ];

        // test each combination once to ensure they are not equal
        for ( $i = 0; $i < count( $test ); $i++ ) {
            for( $j = $i + 1; $j < count( $test ); $j++ )
                $this->assertTrue( $test[ $i ] != $test[ $j ] );
        }
    }

    public function testGuid()
    {
        $guid1 = U::guid();
        $guid2 = U::guid();

        $this->assertEquals( 36, strlen( $guid1 ) );
        $this->assertEquals( 36, strlen( $guid2 ) );
        $this->assertTrue( $guid1 != $guid2 );

        $guid1 = U::guid( false );
        $guid2 = U::guid( false );

        $this->assertEquals( 32, strlen( $guid1 ) );
        $this->assertEquals( 32, strlen( $guid2 ) );
        $this->assertTrue( $guid1 != $guid2 );
    }

    public function testSeoify()
    {
        $this->assertEquals( 'some-test-string', U::seoify( 'some test string' ) );
        $this->assertEquals( 'meh', U::seoify( '*)#%*^&--meh' ) );
        $this->assertEquals( 'already-seoified-string', U::seoify( 'already-seoified-string' ) );
    }

    public function testParseMetricStr()
    {
        $this->assertEquals( 1000000000000, U::parse_metric_str( '1T' ) );
        $this->assertEquals( 50000000000, U::parse_metric_str( '50G' ) );
        $this->assertEquals( 1400000, U::parse_metric_str( '1.4M' ) );
        $this->assertEquals( 2000, U::parse_metric_str( '2K' ) );

        $this->assertEquals( 1073741824, U::parse_metric_str( '1GBytes', true ) );
    }

    public function testNumberAbbreviate()
    {
        $this->assertEquals( '12.3K', U::number_abbreviate( 12345 ) );
        $this->assertEquals( '1M', U::number_abbreviate( 1000000, 2 ) );

        $this->assertEquals( '-1234', U::number_abbreviate( -1234, 2 ) );
        $this->assertEquals( '123', U::number_abbreviate( 123, 3 ) );
        $this->assertEquals( '12.345K', U::number_abbreviate( 12345, 3 ) );
        $this->assertEquals( '12.345M', U::number_abbreviate( 12345000, 3 ) );
        $this->assertEquals( '1.23G', U::number_abbreviate( 1234567890, 2 ) );
        $this->assertEquals( '1.23T', U::number_abbreviate( 1234567890123, 2 ) );
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

        $cookieStr = U::set_cookie_fix_domain(
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

    public function testTimeAgo()
    {
        $this->assertEquals('10 seconds ago', U::timeAgo(time() - 10));
        $this->assertEquals('5 minutes ago', U::timeAgo(time() - 300));
        $this->assertEquals('1 day ago', U::timeAgo(time() - 86401));
        $this->assertEquals('1 week ago', U::timeAgo(time() - 86400 * 7));
        $this->assertEquals('1 month ago', U::timeAgo(time() - 86400 * 31));
        $this->assertEquals('1 year ago', U::timeAgo(time() - 86400 * 365));

        $this->assertEquals('1 day, 1 minute, 40 seconds ago', U::timeAgo(time() - 86500, true));
    }

    public function testTimeUntil()
    {
        $this->assertEquals('10 seconds', U::timeUntil(time() + 10));
        $this->assertEquals('5 minutes', U::timeUntil(time() + 300));
        $this->assertEquals('1 day', U::timeUntil(time() + 86401));
        $this->assertEquals('1 week', U::timeUntil(time() + 86400 * 7));
        $this->assertEquals('1 month', U::timeUntil(time() + 86400 * 32));
        $this->assertEquals('1 year', U::timeUntil(time() + 86400 * 365));

        $this->assertEquals('1 day, 1 minute, 40 seconds', U::timeUntil(time() + 86500, true));
    }
}
