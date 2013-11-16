<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.2
 * @copyright 2013 Jared King
 * @license MIT
 */

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

use infuse\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
	public function testTodo()
	{
		Model::configure(array());
		
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}
}