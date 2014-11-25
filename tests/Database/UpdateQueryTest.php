<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
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
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->values(['test1' => 1, 'test2' => 2]));
        $this->assertEquals($query, $query->values(['test3' => 3]));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\SetStatement', $query->getSet());
        $this->assertEquals(['test1' => 1, 'test2' => 2, 'test3' => 3], $query->getSet()->getValues());
    }

    public function testWhere()
    {
        $query = new UpdateQuery();

        $this->assertEquals($query, $query->where('balance', 10, '>'));
        $this->assertEquals($query, $query->where('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10], ['notes IS NULL']], $where->getConditions());
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

        $query->table('Users')->where('uid', 10)->values(['test' => 'hello', 'test2' => 'field'])
              ->orderBy('uid', 'ASC')->limit(100);

        $this->assertEquals('UPDATE `Users` SET `test`=?,`test2`=? WHERE `uid`=? ORDER BY `uid` ASC LIMIT 100', $query->build());

        // test values
        $this->assertEquals(['hello', 'field', 10], $query->getValues());
    }
}
