<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\Model\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $query = new Query('TestModel');
        $this->assertEquals('TestModel', $query->getModel());
    }

    public function testLimit()
    {
        $query = new Query();

        $this->assertEquals(100, $query->getLimit());
        $this->assertEquals($query, $query->limit(500));
        $this->assertEquals(500, $query->getLimit());
    }

    public function testStart()
    {
        $query = new Query();

        $this->assertEquals(0, $query->getStart());
        $this->assertEquals($query, $query->start(10));
        $this->assertEquals(10, $query->getStart());
    }

    public function testSort()
    {
        $query = new Query();

        $this->assertEquals([], $query->getSort());
        $this->assertEquals($query, $query->sort('name asc, id DESC,invalid,wrong direction'));
        $this->assertEquals([['name', 'asc'], ['id', 'desc']], $query->getSort());
    }

    public function testWhere()
    {
        $query = new Query();

        $this->assertEquals([], $query->getWhere());
        $this->assertEquals($query, $query->where(['test' => true]));
        $this->assertEquals(['test' => true], $query->getWhere());

        $query->where('test', false);
        $this->assertEquals(['test' => false], $query->getWhere());

        $query->where('some condition');
        $this->assertEquals(['test' => false, 'some condition'], $query->getWhere());

        $query->where('balance', 100, '>=');
        $this->assertEquals(['test' => false, 'some condition', ['balance', 100, '>=']], $query->getWhere());
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
               ->withArgs([$query])
               ->andReturn($data);

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

    public function testExecuteMultipleIds()
    {
        $query = new Query('TestModel2');

        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $data = [
            [
                'id' => 100,
                'id2' => 101,
            ],
            [
                'id' => 102,
                'id2' => 103,
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf('TestModel2', $model);
        }

        $this->assertEquals('100,101', $result[0]->id());
        $this->assertEquals('102,103', $result[1]->id());
    }

    public function testAll()
    {
        $query = new Query('TestModel');

        $all = $query->all();
        $this->assertInstanceOf('infuse\\Model\\Iterator', $all);
    }

    public function testFirst()
    {
        $query = new Query('Person');

        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->first();

        $this->assertInstanceOf('Person', $result);
        $this->assertEquals(100, $result->id());
        $this->assertEquals('Sherlock', $result->name);
    }

    public function testFirstLimit()
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
               ->withArgs([$query])
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->first(2);

        $this->assertEquals(2, $query->getLimit());

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
