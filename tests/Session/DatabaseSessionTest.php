<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Session\Database as DatabaseSession;

class DatabaseSessionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $session = new DatabaseSession();
    }
}
