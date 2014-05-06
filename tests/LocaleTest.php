<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18.1
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Locale;

class LocaleTest extends \PHPUnit_Framework_TestCase
{
	static $locale;

	public static function setUpBeforeClass()
	{
		self::$locale = Locale::locale();
		self::$locale->setLocaleDataDir( 'tests/locales' );
	}

	protected function assertPreConditions()
	{
		$this->assertInstanceOf( '\\infuse\\Locale', self::$locale );
	}

	public function testSetLocaleDataDir()
	{
		self::$locale->setLocaleDataDir( 'tests/locales' );
	}

	public function testGetAndSetLocale()
	{
		self::$locale->setLocale( 'pirate' );
		$this->assertEquals( 'pirate', self::$locale->getLocale() );
	}

	public function testTranslate()
	{
		self::$locale->setLocale( 'en' );

		// test phrase
		$this->assertEquals( 'This is a test', self::$locale->translate( 'test_phrase' ) );

		// non-existent phrase
		$this->assertEquals( 'non_existent_phrase', self::$locale->t( 'non_existent_phrase' ) );

		// non-existent locale
		$this->assertEquals( 'some_phrase', self::$locale->t( 'some_phrase', array(), 'pirate' ) );
	}

	public function testTranslateParameterInjection()
	{
		self::$locale->setLocale( 'en' );

		$parameters = array(
			'parameter_1' => 1,
			'test' => 'testing',
			'blah' => 'blah' );

		$expected = 'Testing parameter injection: 1 blah testing';

		$this->assertEquals( $expected, self::$locale->t( 'parameter_injection', $parameters ) );
	}

	public function testPluralize()
	{
		$this->assertEquals( 'points', self::$locale->pluralize( 100, 'point', 'points' ) );

		$this->assertEquals( 'hour', self::$locale->p( 1, 'hour', 'hours' ) );
	}

	public function testCurrencyOptions()
	{
		$optionsStr = self::$locale->currencyOptions();
		$this->assertGreaterThan( 1, strlen( $optionsStr ) );

		$optionsStr2 = self::$locale->currencyOptions( 'USD' );
		$this->assertGreaterThan( 1, strlen( $optionsStr2 ) );
		$this->assertNotEquals( $optionsStr, $optionsStr2 );
	}

	public function testTimezoneOptions()
	{
		$optionsStr = self::$locale->timezoneOptions();
		$this->assertGreaterThan( 1, strlen( $optionsStr ) );

		$optionsStr2 = self::$locale->timezoneOptions( 'America/Chicago' );
		$this->assertGreaterThan( 1, strlen( $optionsStr2 ) );
		$this->assertNotEquals( $optionsStr, $optionsStr2 );
	}
}