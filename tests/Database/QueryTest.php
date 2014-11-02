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

// using the select query implementation, although these tests should
// apply to any class extending Query
class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('execute')->andReturn(true);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals($stmt, $query->execute());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testOne()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('fetch')->withArgs([PDO::FETCH_ASSOC])
             ->andReturn(['result']);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals(['result'], $query->one());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testAll()
    {
        $this->markTestIncomplete();
    }

    public function testColumn()
    {
        $this->markTestIncomplete();
    }

    public function testScalar()
    {
        $this->markTestIncomplete();
    }
}
