<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testSelect()
    {
        $qb = new QueryBuilder();

        $query = $qb->select();
        $this->assertInstanceOf('\\infuse\\Database\\SelectQuery', $query);
        $this->assertEquals(['*'], $query->getSelect()->getFields());

        $query = $qb->select('test');
        $this->assertInstanceOf('\\infuse\\Database\\SelectQuery', $query);
        $this->assertEquals(['test'], $query->getSelect()->getFields());
    }

    public function testInsert()
    {
        $qb = new QueryBuilder();

        $query = $qb->insert(['test' => 'hello']);
        $this->assertInstanceOf('\\infuse\\Database\\InsertQuery', $query);
        $this->assertEquals(['test' => 'hello'], $query->getInsertValues()->getValues());
    }

    public function testUpdate()
    {
        $qb = new QueryBuilder();

        $query = $qb->update('Users');
        $this->assertInstanceOf('\\infuse\\Database\\UpdateQuery', $query);
        $this->assertEquals(['Users'], $query->getTable()->getTables());
    }

    public function testDelete()
    {
        $qb = new QueryBuilder();

        $query = $qb->delete('Users');
        $this->assertInstanceOf('\\infuse\\Database\\DeleteQuery', $query);
        $this->assertEquals(['Users'], $query->getFrom()->getTables());
    }

    public function testRaw()
    {
        $qb = new QueryBuilder();

        $query = $qb->raw('TRUNCATE TABLE Users');
        $this->assertInstanceOf('\\infuse\\Database\\SqlQuery', $query);
        $this->assertEquals('TRUNCATE TABLE Users', $query->build());
    }
}
