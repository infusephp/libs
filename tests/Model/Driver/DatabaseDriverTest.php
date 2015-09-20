<?php

use infuse\Model;
use infuse\Model\Driver\DatabaseDriver;

class DatabaseDriverTest extends PHPUnit_Framework_TestCase
{
    public function testSerializeValue()
    {
        $driver = new DatabaseDriver();

        $property = ['type' => Model::TYPE_STRING];
        $this->assertEquals('string', $driver->serializeValue($property, 'string'));

        $property = ['type' => Model::TYPE_JSON];
        $obj = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($property, $obj));
    }

    public function testUnserializeValue()
    {
        $driver = new DatabaseDriver();

        $property = ['null' => true];
        $this->assertEquals(null, $driver->unserializeValue($property, ''));

        $property = ['type' => Model::TYPE_STRING, 'null' => false];
        $this->assertEquals('string', $driver->unserializeValue($property, 'string'));

        $property = ['type' => Model::TYPE_BOOLEAN, 'null' => false];
        $this->assertTrue($driver->unserializeValue($property, true));
        $this->assertTrue($driver->unserializeValue($property, '1'));
        $this->assertFalse($driver->unserializeValue($property, false));

        $property = ['type' => Model::TYPE_NUMBER, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));

        $property = ['type' => Model::TYPE_DATE, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), $driver->unserializeValue($property, 'Aug-20-2015'));

        $property = ['type' => Model::TYPE_JSON, 'null' => false];
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, '{"test":true}'));
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, ['test' => true]));
    }
}
