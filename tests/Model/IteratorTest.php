<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Model;
use infuse\Model\Iterator;

class IteratorTest extends \PHPUnit_Framework_TestCase
{
	static $iterator;
	private $start = 10;
	private $limit = 50;

	function testIterator()
	{
		self::$iterator = new Iterator( 'IteratorTestModel', [
			'start' => $this->start,
			'limit' => $this->limit ] );
		$this->assertInstanceOf( '\\infuse\\Model\\Iterator', self::$iterator );
	}

	/**
	 * @depends testIterator
	 */
	function testKey()
	{
		$this->assertEquals( $this->start, self::$iterator->key() );
	}

	/**
	 * @depends testIterator
	 */
	function testValid()
	{
		$this->assertTrue( self::$iterator->valid() );
	}

	/**
	 * @depends testIterator
	 */
	function testNext()
	{
		for( $i = $this->start; $i < $this->limit + 1; $i++ )
			self::$iterator->next();

		$this->assertEquals( $this->limit + 1, self::$iterator->key() );
	}

	/**
	 * @depends testIterator
	 */
	function testRewind()
	{
		self::$iterator->rewind();

		$this->assertEquals( $this->start, self::$iterator->key() );
	}

	/**
	 * @depends testIterator
	 */
	function testCurrent()
	{
		self::$iterator->rewind();

		$count = IteratorTestModel::totalRecords();
		for( $i = $this->start; $i < $count + 1; $i++ )
		{
			$current = self::$iterator->current();
			if( $i < $count )
				$this->assertEquals( $i, $current->id() );
			else
				$this->assertEquals( null, $current );

			self::$iterator->next();
		}
	}

	/**
	 * @depends testIterator
	 */
	function testNotValid()
	{
		self::$iterator->rewind();
		for( $i = $this->start; $i < IteratorTestModel::totalRecords() + 1; $i++ )
			self::$iterator->next();

		$this->assertFalse( self::$iterator->valid() );
	}

	/**
	 * @depends testIterator
	 */
	function testForeach()
	{
		$i = $this->start;
		foreach( self::$iterator as $k => $model )
		{
			$this->assertEquals( $i, $k );
			$this->assertEquals( $i, $model->id() );
			$i++;
		}

		$this->assertEquals( $i, IteratorTestModel::totalRecords() );
	}

	/**
	 * @depends testIterator
	 */
	function testFindAll()
	{
		$iterator = IteratorTestModel::findAll();

		$i = 0;
		foreach( $iterator as $k => $model )
		{
			$this->assertEquals( $i, $k );
			$this->assertEquals( $i, $model->id() );
			$i++;
		}

		$this->assertEquals( $i, IteratorTestModel::totalRecords() );
	}

	function testFromZero()
	{
		$start = 0;
		$limit = 101;
		$iterator = new Iterator( 'IteratorTestModel', [
			'start' => $start,
			'limit' => $limit ] );
		
		$i = $start;
		foreach( $iterator as $k => $model )
		{
			$this->assertEquals( $i, $k );
			$this->assertEquals( $i, $model->id() );
			$i++;
		}

		$this->assertEquals( $i, IteratorTestModel::totalRecords()	 );
	}
}

class IteratorTestModel extends Model
{
	static function idProperty()
	{
		return [ 'id', 'id2' ];
	}
	protected function hasPermission( $permission, Model $requester )
	{
		return true;
	}

	static function totalRecords( array $where = [] )
	{
		return 123;
	}

	static function find( array $params = [] )
	{
		if( $params[ 'sort' ] != 'id ASC,id2 ASC')
			return [ 'models' => [], 'count' => 0 ];

		$range = range( $params[ 'start' ], $params[ 'start' ] + $params[ 'limit' ] - 1 );
		$models = [];
		$modelClass = get_called_class();

		foreach( $range as $k )
			$models[] = new $modelClass( $k );

		return [
			'models' => $models,
			'count' => self::totalRecords() ];
	}
}