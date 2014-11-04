<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\Statements\ValuesStatement;

class ValuesStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testValues()
    {
        $stmt = new ValuesStatement();
        $this->assertEquals($stmt, $stmt->addValues(['test' => 1, 'test2' => 2]));
        $this->assertEquals($stmt, $stmt->addValues(['test3' => 3]));
        $this->assertEquals(['test' => 1, 'test2' => 2, 'test3' => 3], $stmt->getValues());
    }

    public function testBuild()
    {
        $stmt = new ValuesStatement();
        $this->assertEquals('', $stmt->build());

        $stmt->addValues(['test' => 1, 'should"_not===_work' => 'fail']);
        $this->assertEquals('(`test`) VALUES (?)', $stmt->build());

        $stmt->addValues(['test2' => 2]);
        $this->assertEquals('(`test`,`test2`) VALUES (?,?)', $stmt->build());
    }
}
