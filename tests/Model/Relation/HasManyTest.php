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
use Infuse\Model\Relation\HasMany;

class HasManyTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');

        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 'result'], ['id' => 'result2']]);

        Model::setDriver($driver);
    }

    public function testInitQuery()
    {
        $model = new TestModel2();
        $model->id = 10;

        $relation = new HasMany('TestModel', 'test_model_id', 'id', $model);

        $this->assertEquals(['test_model_id' => 10], $relation->getQuery()->getWhere());
    }

    public function testGetResults()
    {
        $model = new TestModel2();
        $model->id = 10;

        $relation = new HasMany('TestModel', 'test_model_id', 'id', $model);

        $result = $relation->getResults();

        $this->assertCount(2, $result);

        foreach ($result as $m) {
            $this->assertInstanceOf('TestModel', $m);
        }

        $this->assertEquals('result', $result[0]->id());
        $this->assertEquals('result2', $result[1]->id());
    }
}
