<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Database\SqlQuery;

class SqlQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testRaw()
    {
        $query = new SqlQuery();
        $query->raw('SHOW COLUMNS FROM test');
        $this->assertEquals('SHOW COLUMNS FROM test', $query->build());
    }
}
