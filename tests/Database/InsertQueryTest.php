<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\InsertQuery;

class InsertQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $query = new InsertQuery();

        $this->assertEquals($query, $query->into('Users'));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\FromStatement', $query->getInto());
        $this->assertFalse($query->getInto()->hasFrom());
        $this->assertEquals(['Users'], $query->getInto()->getTables());
    }

    public function testValues()
    {
        $query = new InsertQuery();

        $this->assertEquals($query, $query->values(['test1' => 1, 'test2' => 2]));
        $this->assertEquals($query, $query->values(['test3' => 3]));
        $this->assertInstanceOf('\\infuse\\Database\\Statements\\ValuesStatement', $query->getInsertValues());
        $this->assertEquals(['test1' => 1, 'test2' => 2, 'test3' => 3], $query->getInsertValues()->getValues());
    }

    public function testBuild()
    {
        $query = new InsertQuery();

        $query->into('Users')->values(['field1' => 'what', 'field2' => 'test']);

        $this->assertEquals('INSERT INTO `Users` (`field1`,`field2`) VALUES (?,?)', $query->build());

        // test values
        $this->assertEquals(['what', 'test'], $query->getValues());
    }
}
