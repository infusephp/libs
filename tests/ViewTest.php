<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\View;
use infuse\ViewEngine;
use Pimple\Container;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public static $viewsDir;

    public function testDefaultEngine()
    {
        $this->assertInstanceOf('\\infuse\\ViewEngine\\PHP', View::defaultEngine());

        $engine = new ViewEngine\PHP();
        $container = new Container();
        $container['view_engine'] = $engine;
        View::inject($container);
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
        $this->assertEquals(['viewsDir'=>'BLAH'], $view->getParameters());
    }

    public function testParameters()
    {
        $view = new View('test');
        $view->setParameters(['test' => true, 'test2' => 'blah']);
        $view->setParameters(['test' => 'overwrite']);

        $this->assertEquals(['test'=>'overwrite', 'test2'=>'blah','viewsDir'=>'BLAH'], $view->getParameters());
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