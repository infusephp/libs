<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\InsertQuery;

class InsertQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $query = new InsertQuery();

        $this->assertEquals($query, $query->into('Users'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\FromStatement', $query->getInto());
        $this->assertFalse($query->getInto()->hasFrom());
        $this->assertEquals(['Users'], $query->getInto()->getTables());
    }

    public function testValues()
    {
        // TODO values

        $this->markTestIncomplete();
    }

    public function testBuild()
    {
        $query = new InsertQuery();

        // TODO values

        $query->into('Users');

        $this->assertEquals('INSERT INTO `Users`', $query->build());

        // test values
        $this->assertEquals([], $query->getValues());
    }
}
