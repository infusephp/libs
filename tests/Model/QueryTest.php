<?php

use infuse\Model\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{
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
    }

    public function testExecute()
    {
        $query = new Query();

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

        Person::setDriver($driver);

        $result = $query->execute('Person');

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf('Person', $model);
        }

        $this->assertEquals(100, $result[0]->id());
        $this->assertEquals(102, $result[1]->id());

        $this->assertEquals('Sherlock', $result[0]->name);
        $this->assertEquals('John', $result[1]->name);
    }

    public function testFirst()
    {
        $query = new Query();

        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->first('Person');

        $this->assertInstanceOf('Person', $result);
        $this->assertEquals(100, $result->id());
        $this->assertEquals('Sherlock', $result->name);
    }
}
