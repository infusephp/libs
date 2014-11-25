<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\Statements\OrderStatement;

class OrderStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupBy()
    {
        $stmt = new OrderStatement();
        $this->assertFalse($stmt->isGroupBy());

        $stmt = new OrderStatement(true);
        $this->assertTrue($stmt->isGroupBy());
    }

    public function testAddFields()
    {
        $stmt = new OrderStatement();
        $this->assertEquals($stmt, $stmt->addFields(['test', 'test2']));
        $this->assertEquals([['test'], ['test2']], $stmt->getFields());

        $this->assertEquals($stmt, $stmt->addFields(['test3']));
        $this->assertEquals([['test'], ['test2'], ['test3']], $stmt->getFields());
    }

    public function testAddFieldsString()
    {
        $stmt = new OrderStatement();
        $this->assertEquals($stmt, $stmt->addFields('test'));
        $this->assertEquals([['test']], $stmt->getFields());

        $stmt = new OrderStatement();
        $this->assertEquals($stmt, $stmt->addFields('test ASC, test2'));
        $this->assertEquals([['test','ASC'],['test2']], $stmt->getFields());

        $stmt = new OrderStatement();
        $this->assertEquals($stmt, $stmt->addFields('test', 'ASC'));
        $this->assertEquals([['test','ASC']], $stmt->getFields());
    }

    public function testBuild()
    {
        $stmt = new OrderStatement();
        $stmt->addFields('user_name', 'ASC')
             ->addFields('user_email')
             ->addFields('uid', 'DESC');

        $this->assertEquals('ORDER BY `user_name` ASC,`user_email`,`uid` DESC', $stmt->build());

        $stmt = new OrderStatement(true);
        $stmt->addFields('test', 'ASC');

        $this->assertEquals('GROUP BY `test` ASC', $stmt->build());

        $stmt = new OrderStatement();
        $this->assertEquals('', $stmt->build());
    }

    public function testBuildInvalidIdentifier()
    {
        $stmt = new OrderStatement();
        $stmt->addFields([[['test']]])->addFields('should"_not===_work');
        $this->assertEquals('', $stmt->build());

        $stmt = new OrderStatement();
        $stmt->addFields(1);
        $this->assertEquals('', $stmt->build());
    }
}
