<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\Statements\FromStatement;

class FromStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testAddTable()
    {
        $stmt = new FromStatement();
        $this->assertEquals($stmt, $stmt->addTable(['test','test2']));
        $this->assertEquals(['test','test2'], $stmt->getTables());

        $this->assertEquals($stmt, $stmt->addTable(['test3']));
        $this->assertEquals(['test','test2','test3'], $stmt->getTables());
    }

    public function testAddTableString()
    {
        $stmt = new FromStatement();
        $this->assertEquals($stmt, $stmt->addTable('test'));
        $this->assertEquals(['test'], $stmt->getTables());

        $stmt = new FromStatement();
        $this->assertEquals($stmt, $stmt->addTable('test, test2'));
        $this->assertEquals(['test','test2'], $stmt->getTables());
    }

    public function testBuild()
    {
        $stmt = new FromStatement();
        $this->assertEquals($stmt, $stmt->addTable('test,test2,should"_not===_work'));
        $this->assertEquals('FROM `test`,`test2`', $stmt->build());

        $stmt = new FromStatement();
        $this->assertEquals('', $stmt->build());
    }
}
