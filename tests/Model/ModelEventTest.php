<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Model\ModelEvent;

class ModelEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $model = Mockery::mock('Infuse\Model');
        $event = new ModelEvent($model);
        $this->assertEquals($model, $event->getModel());
    }
}
