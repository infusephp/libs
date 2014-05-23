<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
	public function testLoad()
	{
		$testConfig = array(
			'test' => 1,
			'test2' => array(
				2,
				3
			),
			'test3' => array(
				'does' => 'this',
				'thing' => 'work?'
			)
		);

		Config::load( $testConfig );

		$this->assertEquals( Config::get(), $testConfig );
	}

	/**
	 * @depends testLoad
	 */	
	public function testSetandGet()
	{
		Config::set( 'test-property', 'abc' );
		$this->assertEquals( Config::get( 'test-property' ), 'abc' );

		Config::set( 'test.1.2.3', 'test' );
		$this->assertEquals( Config::get( 'test.1.2.3' ), 'test' );

		Config::set( 'test-property', 'blah' );
		$this->assertEquals( Config::get( 'test-property' ), 'blah' );

		$this->assertEquals( Config::get( 'some.invalid.property' ), null );
	}

	/**
	 * @depends testLoad
	 */
	public function testSetandGetDeprecated()
	{
		Config::set( 'section', 'property', 'abc' );
		$this->assertEquals( Config::get( 'section', 'property' ), 'abc' );

		Config::set( 'test.1.2.3', 'property', 'test' );
		$this->assertEquals( Config::get( 'test.1.2.3', 'property' ), 'test' );

		Config::set( 'section', 'property', 'overwrite' );
		$this->assertEquals( Config::get( 'section', 'property' ), 'overwrite' );

		$this->assertEquals( Config::get( 'some', 'invalid-property' ), null );
	}

	/**
	 * @depends testLoad
	 */
	public function testGetSection()
	{

	}
}