<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Model;
use Infuse\Model\Relation\HasOne;

class HasOneTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');

        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 'result']]);

        Model::setDriver($driver);
    }

    public function testInitQuery()
    {
        $model = new TestModel2();
        $model->id = 10;

        $relation = new HasOne('TestModel', 'test_model_id', 'id', $model);

        $this->assertEquals(['test_model_id' => 10], $relation->getQuery()->getWhere());
        $this->assertEquals(1, $relation->getQuery()->getLimit());
    }

    public function testGetResults()
    {
        $model = new TestModel2();
        $model->id = 10;

        $relation = new HasOne('TestModel', 'test_model_id', 'id', $model);

        $result = $relation->getResults();
        $this->assertInstanceOf('TestModel', $result);
        $this->assertEquals('result', $result->id());
    }
}
