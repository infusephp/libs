<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\DeleteQuery;

class DeleteQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testFrom()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->from('Users'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\FromStatement', $query->getFrom());
        $this->assertEquals(['Users'], $query->getFrom()->getTables());
    }

    public function testWhere()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->where('balance', 10, '>'));
        $this->assertEquals($query, $query->where('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10], ['notes IS NULL']], $where->getConditions());
    }

    public function testOrderBy()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->orderBy('uid', 'ASC'));
        $orderBy = $query->getOrderBy();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\OrderStatement', $orderBy);
        $this->assertFalse($orderBy->isGroupBy());
        $this->assertEquals([['uid', 'ASC']], $orderBy->getFields());
    }

    public function testLimit()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->limit(10));
        $this->assertEquals('10', $query->getLimit());

        $this->assertEquals($query, $query->limit('hello'));
        $this->assertEquals('10', $query->getLimit());
    }

    public function testBuild()
    {
        $query = new DeleteQuery();

        $query->from('Users')->where('uid', 10)->limit(100)->orderBy('uid', 'ASC');

        $this->assertEquals('DELETE FROM `Users` WHERE `uid`=? ORDER BY `uid` ASC LIMIT 100', $query->build());

        // test values
        $this->assertEquals([10], $query->getValues());
    }
}
