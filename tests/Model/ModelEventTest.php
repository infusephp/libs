<?php

use infuse\Model\ModelEvent;

class ModelEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $model = Mockery::mock('infuse\Model');
        $event = new ModelEvent($model);
        $this->assertEquals($model, $event->getModel());
    }
}
