<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\UpdateQuery;

class UpdateQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->table('Users'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\FromStatement', $query->getTable());
        $this->assertFalse($query->getTable()->hasFrom());
        $this->assertEquals(['Users'], $query->getTable()->getTables());
    }

    public function testValues()
    {
        // TODO values
        $this->markTestIncomplete();
    }

    public function testWhere()
    {
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->where('balance', 10, '>'));
        $where = $query->getWhere();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10]], $where->getConditions());
    }

    public function testOrderBy()
    {
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->orderBy('uid', 'ASC'));
        $orderBy = $query->getOrderBy();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\OrderStatement', $orderBy);
        $this->assertFalse($orderBy->isGroupBy());
        $this->assertEquals([['uid', 'ASC']], $orderBy->getFields());
    }

    public function testLimit()
    {
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->limit(10));
        $this->assertEquals('10', $query->getLimit());

        $this->assertEquals($query, $query->limit('hello'));
        $this->assertEquals('10', $query->getLimit());
    }

    public function testBuild()
    {
        $query = new UpdateQuery();

        // TODO values

        $query->table('Users')->where('uid', 10)->limit(100)->orderBy('uid', 'ASC');

        $this->assertEquals('UPDATE `Users` WHERE `uid`=? ORDER BY `uid` ASC LIMIT 100', $query->build());

        // test values
        $this->assertEquals([10], $query->getValues());
    }
}
