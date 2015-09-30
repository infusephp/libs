<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $testConfig = [
            'test' => 1,
            'test2' => [
                2,
                3,
            ],
            'test3' => [
                'does' => 'this',
                'thing' => 'work?',
            ],
        ];

        $config = new Config($testConfig);

        $this->assertEquals($config->get(), $testConfig);
    }

    public function testSetandGet()
    {
        $config = new Config();

        $config->set('test-property', 'abc');
        $this->assertEquals($config->get('test-property'), 'abc');

        $config->set('test.1.2.3', 'test');
        $this->assertEquals($config->get('test.1.2.3'), 'test');

        $config->set('test-property', 'blah');
        $this->assertEquals($config->get('test-property'), 'blah');

        $this->assertEquals($config->get('some.invalid.property'), null);

        $expected = [
            'test-property' => 'blah',
            'test' => [
                '1' => [
                    '2' => [
                        '3' => 'test', ], ], ], ];
        $this->assertEquals($expected, $config->get());
    }
}
