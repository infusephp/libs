<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2013 Jared King
 * @license MIT
 */

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

	function testConfigure()
	{
		TestModel::configure( array(
			'test' => 123,
			'test2' => 12345 ) );

		$this->assertEquals( 123, TestModel::getConfigValue( 'test' ) );
		$this->assertEquals( 12345, TestModel::getConfigValue( 'test2' ) );
	}

	function testId()
	{
		$model = new TestModel( 5 );

		$this->assertEquals( 5, $model->id() );
	}

	function testMultipleIds()
	{
		$model = new TestModel2( array( 5, 2 ) );

		$this->assertEquals( '5,2', $model->id() );
	}

	function testIdKeyValue()
	{
		$model = new TestModel( 3 );
		$this->assertEquals( array( 'id' => 3 ), $model->id( true ) );

		$model = new TestModel2( array( 5, 2 ) );
		$this->assertEquals( array( 'id' => 5, 'id2' => 2 ), $model->id( true ) );
	}

	function testToString()
	{
		$model = new TestModel( 1 );
		$this->assertEquals( 'TestModel(1)', (string)$model );
	}

	function testSetProperty()
	{
		$model = new TestModel( 2 );

		$model->test = 12345;
		$this->assertEquals( 12345, $model->test );
	}

	function testIsset()
	{
		$model = new TestModel( 1 );

		$this->assertFalse( isset( $model->test2 ) );

		$model->test = 12345;
		$this->assertTrue( isset( $model->test ) );
	}

	function testUnset()
	{
		$model = new TestModel( 1 );

		$model->test = 12345;
		unset( $model->test );
		$this->assertFalse( isset( $model->test ) );
	}

	function testInfo()
	{
		$expected = array(
			'model' => 'TestModel',
			'class_name' => 'TestModel',
			'singular_key' => 'test_model',
			'plural_key' => 'test_models',
			'proper_name' => 'Test Model',
			'proper_name_plural' => 'Test Models' );

		$this->assertEquals( $expected, TestModel::info() );
	}

	function testTablename()
	{
		$this->assertEquals( 'TestModels', TestModel::tablename() );
	}

	function testHasNoId()
	{
		$model = new TestModel;
		$this->assertFalse( $model->id() );
	}

	function testIsIdProperty()
	{
		$this->assertFalse( TestModel::isIdProperty( 'blah' ) );
		$this->assertTrue( TestModel::isIdProperty( 'id' ) );
		$this->assertTrue( TestModel2::isIdProperty( 'id2' ) );
	}

	function testRelation()
	{
		$model = new TestModel;
		$model->relation = 2;

		$relation = $model->relation( 'relation' );
		$this->assertInstanceOf( 'TestModel2', $relation );
		$this->assertEquals( 2, $relation->id() );

		// test if relation model is cached
		$relation->test = 'hello';
		$relation2 = $model->relation( 'relation' );
		$this->assertEquals( 'hello', $relation2->test );
	}

	function testToArray()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$expected = array(
			'id' => 5,
			'relation' => 'test'
		);

		$this->assertEquals( $expected, $model->toArray() );
	}

	function testToArrayExcluded()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$expected = array(
			'id' => 5
		);

		$this->assertEquals( $expected, $model->toArray( array( 'relation' ) ) );
	}

	function testToJson()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$this->assertEquals( '{"id":5,"relation":"test"}', $model->toJson() );
	}

	function testHasSchema()
	{
		$this->assertTrue( TestModel::hasSchema() );
		$this->assertFalse( TestModel2::hasSchema() );
	}
}

class TestModel extends Model
{
	static $properties = array(
		'id' => array(
			'type' => 'id'
		),
		'relation' => array(
			'type' => 'id',
			'relation' => 'TestModel2'
		)
	);
}

class TestModel2 extends Model
{
	static $idProperty = array( 'id', 'id2' );
	static $properties = array(
		'id' => array(
			'type' => 'id'
		),
		'id2' => array(
			'type' => 'id'
		)
	);
	static $hasSchema = false;
}