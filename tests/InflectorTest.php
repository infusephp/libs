<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.0
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Inflector;

class InflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testPluralize()
    {
        $this->assertEquals( 'posts', Inflector::pluralize( 'post' ) );
    }

    public function testSingularize()
    {
        $this->assertEquals( 'post', Inflector::singularize( 'posts' ) );
    }

    public function testCamelize()
    {
        $this->assertEquals( 'BlogPost', Inflector::camelize( 'blog_post' ) );
        $this->assertEquals( 'blogPost', Inflector::camelize( 'blog_post', true ) );
    }

    public function testUnderscore()
    {
        $this->assertEquals( 'blog_post', Inflector::underscore( 'BlogPost' ) );
    }

    public function testHumanize()
    {
        $this->assertEquals( 'Blog post', Inflector::humanize( 'blog_post' ) );
    }

    public function testTitleize()
    {
        $this->assertEquals( 'Blog Post', Inflector::titleize( 'blog_post' ) );
    }

    public function testOrdinal()
    {
        $this->assertEquals( 'nd', Inflector::ordinal( 2 ) );
    }

    public function testOrdinalize()
    {
        $this->assertEquals( '2nd', Inflector::ordinalize( 2 ) );
    }
}
