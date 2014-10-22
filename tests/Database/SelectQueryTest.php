<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\SelectQuery;

class SelectQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testSelect()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->select('name'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\SelectStatement', $query->getSelect());
        $this->assertEquals(['name'], $query->getSelect()->getFields());
    }

    public function testFrom()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->from('Users'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\FromStatement', $query->getFrom());
        $this->assertEquals(['Users'], $query->getFrom()->getTables());
    }

    public function testWhere()
    {
        $this->markTestIncomplete();
    }

    public function testLimit()
    {
        $this->markTestIncomplete();
    }

    public function testGroupBy()
    {
        $this->markTestIncomplete();
    }

    public function testHaving()
    {
        $this->markTestIncomplete();
    }

    public function testOrderBy()
    {
        $this->markTestIncomplete();
    }
}
