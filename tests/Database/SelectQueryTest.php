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
        $query = new SelectQuery();

        $this->assertEquals($query, $query->where('balance', 10, '>'));
        $this->assertEquals($query, $query->where('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10], ['notes IS NULL']], $where->getConditions());
    }

    public function testLimit()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->limit(10));
        $this->assertEquals(['10', '0'], $query->getLimit());

        $this->assertEquals($query, $query->limit(100, 200));
        $this->assertEquals(['100', '200'], $query->getLimit());

        $this->assertEquals($query, $query->limit('hello'));
        $this->assertEquals(['100', '200'], $query->getLimit());
    }

    public function testGroupBy()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->groupBy('uid'));
        $groupBy = $query->getGroupBy();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\OrderStatement', $groupBy);
        $this->assertTrue($groupBy->isGroupBy());
        $this->assertEquals([['uid']], $groupBy->getFields());
    }

    public function testHaving()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->having('balance', 10, '>'));
        $this->assertEquals($query, $query->having('notes IS NULL'));
        $having = $query->getHaving();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\WhereStatement', $having);
        $this->assertTrue($having->isHaving());
        $this->assertEquals([['balance', '>', 10], ['notes IS NULL']], $having->getConditions());
    }

    public function testOrderBy()
    {
        $query = new SelectQuery();

        $this->assertEquals($query, $query->orderBy('uid', 'ASC'));
        $orderBy = $query->getOrderBy();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\OrderStatement', $orderBy);
        $this->assertFalse($orderBy->isGroupBy());
        $this->assertEquals([['uid', 'ASC']], $orderBy->getFields());
    }

    public function testBuild()
    {
        $query = new SelectQuery();

        $query->from('Users')->where('uid', 10)->having('first_name', 'something')
              ->groupBy('last_name')->orderBy('first_name', 'ASC')
              ->limit(100, 10);

        $this->assertEquals('SELECT * FROM `Users` WHERE `uid`=? GROUP BY `last_name` HAVING `first_name`=? ORDER BY `first_name` ASC LIMIT 10,100', $query->build());

        // test values
        $this->assertEquals([10, 'something'], $query->getValues());
    }
}
