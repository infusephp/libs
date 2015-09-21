<?php

use infuse\Model\Query;

require_once 'tests/test_models.php';

class QueryTest extends PHPUnit_Framework_TestCase
{
    public static $originalDriver;

    public static function setUpBeforeClass()
    {
        self::$originalDriver = Person::getDriver();
    }

    public static function tearDownAfterClass()
    {
        Person::setDriver(self::$originalDriver);
    }

    public function testLimit()
    {
        $query = new Query('Person');

        $this->assertEquals(100, $query->getLimit());
        $this->assertEquals($query, $query->setLimit(500));
        $this->assertEquals(500, $query->getLimit());
    }

    public function testStart()
    {
        $query = new Query('Person');

        $this->assertEquals(0, $query->getStart());
        $this->assertEquals($query, $query->setStart(10));
        $this->assertEquals(10, $query->getStart());
    }

    public function testSort()
    {
        $query = new Query('Person');

        $this->assertEquals([], $query->getSort());
        $this->assertEquals($query, $query->setSort('name asc, id DESC,invalid,wrong direction'));
        $this->assertEquals([['name', 'asc'], ['id', 'desc']], $query->getSort());
    }

    public function testWhere()
    {
        $query = new Query('Person');

        $this->assertEquals([], $query->getWhere());
        $this->assertEquals($query, $query->setWhere(['test' => true]));
        $this->assertEquals(['test' => true], $query->getWhere());
    }

    public function testExecute()
    {
        $query = new Query('Person');

        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
            [
                'id' => 102,
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs(['Person', $query])
               ->andReturn($data);

        $driver->shouldReceive('unserializeValue')
               ->andReturnUsing(function ($property, $value) {
                    return $value;
               });

        Person::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf('Person', $model);
        }

        $this->assertEquals(100, $result[0]->id());
        $this->assertEquals(102, $result[1]->id());

        $this->assertEquals('Sherlock', $result[0]->name);
        $this->assertEquals('John', $result[1]->name);
    }
}
