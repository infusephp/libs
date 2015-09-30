<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\Model;
use infuse\Model\Relation\BelongsTo;

class BelongsToTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 'result']]);

        Model::setDriver($driver);
    }

    public function testInitQuery()
    {
        $model = new TestModel2();
        $model->test_model_id = 10;

        $relation = new BelongsTo('TestModel', 'id', 'test_model_id', $model);

        $this->assertEquals(['id' => 10], $relation->getQuery()->getWhere());
        $this->assertEquals(1, $relation->getQuery()->getLimit());
    }

    public function testGetResults()
    {
        $model = new TestModel2();
        $model->test_model_id = 10;

        $relation = new BelongsTo('TestModel', 'id', 'test_model_id', $model);

        $result = $relation->getResults();
        $this->assertInstanceOf('TestModel', $result);
        $this->assertEquals('result', $result->id());
    }
}
