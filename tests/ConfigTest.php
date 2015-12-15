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
    public function testAll()
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

        $this->assertEquals($config->all(), $testConfig);
    }

    public function testSetandGet()
    {
        $config = new Config();

        $config->set('test-property', 'abc');
        $this->assertEquals('abc', $config->get('test-property'));

        $config->set('test.1.2.3', 'test');
        $this->assertEquals('test', $config->get('test.1.2.3'));

        $config->set('test-property', 'blah');
        $this->assertEquals('blah', $config->get('test-property'));

        $this->assertNull($config->get('some.invalid.property'));

        $this->assertEquals('default', $config->get('some.invalid.property', 'default'));
    }
}
