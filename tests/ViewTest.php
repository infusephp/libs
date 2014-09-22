<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\View;
use infuse\ViewEngine;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public static $viewsDir;

    public function testDefaultEngine()
    {
        $this->assertInstanceOf('\\infuse\\ViewEngine\\PHP', View::defaultEngine());

        $engine = new ViewEngine\PHP();
        View::setDefaultEngine($engine);
        $this->assertEquals($engine, View::defaultEngine());
    }

    public function testTemplate()
    {
        $view = new View('test');
        $this->assertEquals('test', $view->template());
    }

    public function testTemplateWithViewsDir()
    {
        self::$viewsDir = 'BLAH';
        $view = new View('test');
        $this->assertEquals('BLAH/test', $view->template());
    }

    public function testParameters()
    {
        $view = new View('test');
        $view->setParameters(['test' => true, 'test2' => 'blah']);
        $view->setParameters(['test' => 'overwrite']);

        $this->assertEquals(['test'=>'overwrite', 'test2'=>'blah'], $view->getParameters());
    }

    public function testEngine()
    {
        $engine = new ViewEngine\PHP();
        $view = new View('test');

        $this->assertInstanceOf('\\infuse\\ViewEngine\\PHP', $view->getEngine());

        $view->setEngine($engine);
        $this->assertEquals($engine, $view->getEngine());
    }

    public function testRender()
    {
        $engine = Mockery::mock('ViewEngine');

        $view = new View('test', [], $engine);

        $engine->shouldReceive('renderView')->withArgs([$view])->andReturn('test')->once();

        $this->assertEquals('test', $view->render());
    }
}
