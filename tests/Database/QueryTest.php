<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
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
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals($stmt, $query->execute());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testExecuteFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new SelectQuery($pdo);

        $this->assertFalse($query->execute());
    }

    public function testOne()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('fetch')->withArgs([PDO::FETCH_ASSOC])
             ->andReturn(['field' => 'value']);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals(['field' => 'value'], $query->one());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testOneFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new SelectQuery($pdo);

        $this->assertFalse($query->one());
    }

    public function testAll()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('fetchAll')->withArgs([PDO::FETCH_ASSOC])
             ->andReturn([['field' => 'value'], ['field' => 'value2']]);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals([['field' => 'value'], ['field' => 'value2']], $query->all());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testAllFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new SelectQuery($pdo);

        $this->assertFalse($query->all());
    }

    public function testColumn()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('fetchAll')->withArgs([PDO::FETCH_COLUMN, 0])
             ->andReturn(['value', 'value2']);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals(['value', 'value2'], $query->column());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testColumnFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new SelectQuery($pdo);

        $this->assertFalse($query->column());
    }

    public function testScalar()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);
        $stmt->shouldReceive('fetchColumn')->withArgs([0])
             ->andReturn('scalar');

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->withArgs(["SELECT * FROM `Test` WHERE `id`=?"])
            ->andReturn($stmt);

        $query = new SelectQuery($pdo);
        $query->from('Test')->where('id', 'test');

        $this->assertEquals('scalar', $query->scalar());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testScalarFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock();
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new SelectQuery($pdo);

        $this->assertFalse($query->scalar());
    }
}
