<?php

use infuse\Model\Relation\Relation;

class RelationTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $this->assertTrue($relation->initQuery);
    }

    public function testGetModel()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $this->assertEquals('TestModel', $relation->getModel());
    }

    public function testGetForeignKey()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $this->assertEquals('id', $relation->getForeignKey());
    }

    public function testGetLocalKey()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $this->assertEquals('user_id', $relation->getLocalKey());
    }

    public function testGetRelation()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $this->assertEquals($model, $relation->getRelation());
    }

    public function testGetQuery()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $query = $relation->getQuery();
        $this->assertInstanceOf('infuse\\Model\\Query', $query);
    }

    public function testCallOnQuery()
    {
        $model = Mockery::mock('infuse\\Model');
        $relation = new DistantRelation('TestModel', 'id', 'user_id', $model);

        $relation->where(['name' => 'Bob']);

        $this->assertEquals(['name' => 'Bob'], $relation->getQuery()->getWhere());
    }
}

class DistantRelation extends Relation
{
    public $initQuery;

    protected function initQuery()
    {
        $this->initQuery = true;
    }

    public function getResults()
    {
        // do nothing
    }
}
