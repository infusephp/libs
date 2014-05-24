<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\AclRequester;
use infuse\ErrorStack;
use infuse\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
	static $requester;

	static function setUpBeforeClass()
	{
		self::$requester = new Person( 1 );

		Model::configure( [
			'database' => [
				'enabled' => false ],
			'requester' => self::$requester ] );
	}

	function testConfigure()
	{
		TestModel::configure( [
			'test' => 123,
			'test2' => 12345 ] );

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
		$model = new TestModel2( [ 5, 2 ] );

		$this->assertEquals( '5,2', $model->id() );
	}

	function testIdKeyValue()
	{
		$model = new TestModel( 3 );
		$this->assertEquals( [ 'id' => 3 ], $model->id( true ) );

		$model = new TestModel2( [ 5, 2 ] );
		$this->assertEquals( [ 'id' => 5, 'id2' => 2 ], $model->id( true ) );
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

		$model->null = null;
		$this->assertEquals( null, $model->null );
	}

	function testIsset()
	{
		$model = new TestModel( 1 );

		$this->assertFalse( isset( $model->test2 ) );

		$model->test = 12345;
		$this->assertTrue( isset( $model->test ) );

		$model->null = null;
		$this->assertTrue( isset( $model->null ) );
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
		$expected = [
			'model' => 'TestModel',
			'class_name' => 'TestModel',
			'singular_key' => 'test_model',
			'plural_key' => 'test_models',
			'proper_name' => 'Test Model',
			'proper_name_plural' => 'Test Models' ];

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

	function testGetMultipleProperties()
	{
		$model = new TestModel( 3 );
		$model->id = 3;
		$model->relation = 'test';
		$model->answer = 42;

		$expected = array(
			'id' => 3,
			'relation' => 'test',
			'answer' => 42 );

		$values = $model->get( array( 'id', 'relation', 'answer' ) );
		$this->assertEquals( $expected, $values );
	}

	function testGetDefaultValue()
	{
		$model = new TestModel2( 12 );
		$this->assertEquals( 'some default value', $model->get( 'default' ) );
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

		$expected = [
			'id' => 5,
			'relation' => 'test',
			'answer' => null
		];

		$this->assertEquals( $expected, $model->toArray() );
	}

	function testToArrayExcluded()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$expected = [
			'id' => 5
		];

		$this->assertEquals( $expected, $model->toArray( [ 'relation', 'answer' ] ) );
	}

	function testToJson()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$this->assertEquals( '{"id":"5","relation":"test","answer":null}', $model->toJson() );
	}

	function testHasSchema()
	{
		$this->assertTrue( TestModel::hasSchema() );
		$this->assertFalse( TestModel2::hasSchema() );
	}

	function testCache()
	{
		$model = new TestModel( 3 );

		$model->cacheProperties( [
			'test' => 123,
			'test2' => 'hello' ] );
		$this->assertEquals( 123, $model->test );
		$this->assertEquals( 'hello', $model->test2 );

		$model2 = new TestModel( 3 );
		$this->assertEquals( 123, $model2->test );
		$this->assertEquals( 'hello', $model2->test2 );
	}

	function testInvalidateCache()
	{
		$model = new testModel( 4 );

		$model->answer = 42;
		$model->test = 1234;
		$this->assertTrue( isset( $model->answer ) );
		$this->assertTrue( isset( $model->test ) );

		$model->emptyCache();

		$this->assertNotEquals( 42, $model->answer );
		$this->assertFalse( isset( $model->test ) );
	}

	function testCreate()
	{
		$newModel = new TestModel;
		$this->assertTrue( $newModel->create( [ 'relation' => '', 'answer' => 42 ] ) );
		$id = $newModel->id();
		$this->assertTrue( !empty( $id ) );
		$this->assertEquals( null, $newModel->relation );
		$this->assertEquals( 42, $newModel->answer );
	}

	function testCreateMutable()
	{
		$newModel = new TestModel2;
		$this->assertTrue( $newModel->create( [ 'id' => 1, 'id2' => 2, 'required' => 'yes' ] ) );
		$this->assertEquals( '1,2', $newModel->id() );
	}

	function testCreateWithId()
	{
		$model = new TestModel( 5 );
		$this->assertFalse( $model->create( [ 'relation' => '', 'answer' => 42 ] ) );
	}

	function testCreateNoPermission()
	{
		ErrorStack::stack()->clear();
		$newModel = new TestModelNoPermission;
		$this->assertFalse( $newModel->create( [] ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModelNoPermission.create' ) );
	}

	function testCreateHookFail()
	{
		$newModel = new TestModelHookFail;
		$this->assertFalse( $newModel->create( [] ) );
	}

	function testCreateNotUnique()
	{
		// TODO
	}

	function testCreateInvalid()
	{
		ErrorStack::stack()->clear();
		$newModel = new TestModel2;
		$this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true ] ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModel2.create' ) );
	}

	function testCreateMissingRequired()
	{
		ErrorStack::stack()->clear();
		$newModel = new TestModel2;
		$this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1 ] ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModel2.create' ) );
	}

	function testSet()
	{
		$model = new TestModel( 10 );

		$this->assertTrue( $model->set( 'answer', 42 ) );
		$this->assertEquals( 42, $model->answer );
	}

	function testSetMultiple()
	{
		$model = new TestModel( 11 );

		$this->assertTrue( $model->set( [
			'answer' => 'hello',
			'relation' => '',
			'nonexistent_property' => 'whatever' ] ) );
		$this->assertEquals( 'hello', $model->answer );
		$this->assertEquals( null, $model->relation );
	}

	function testSetWithNoId()
	{
		$model = new TestModel;
		$this->assertFalse( $model->set( [ 'answer' => 42 ] ) );
	}

	function testSetNoPermission()
	{
		ErrorStack::stack()->clear();
		$model = new TestModelNoPermission( 5 );
		$this->assertFalse( $model->set( 'answer', 42 ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModelNoPermission.set' ) );
	}

	function testSetHookFail()
	{
		$model = new TestModelHookFail( 5 );
		$this->assertFalse( $model->set( 'answer', 42 ) );
	}

	function testSetNotUnique()
	{
		// TODO
	}

	function testSetInvalid()
	{
		ErrorStack::stack()->clear();
		$model = new TestModel2( 15 );

		$this->assertFalse( $model->set( 'validate', 'not a valid email' ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModel2.set' ) );
	}

	function testDelete()
	{
		$model = new TestModel2( 1 );
		$this->assertTrue( $model->delete() );
	}

	function testDeleteWithNoId()
	{
		$model = new TestModel;
		$this->assertFalse( $model->delete() );
	}

	function testDeleteWithHook()
	{
		$model = new TestModel( 100 );

		$this->assertTrue( $model->delete() );
		$this->assertTrue( $model->preDelete );
		$this->assertTrue( $model->postDelete );
	}

	function testDeleteNoPermission()
	{
		$model = new TestModelNoPermission( 5 );
		$this->assertFalse( $model->delete() );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModelNoPermission.delete' ) );
	}

	function testDeleteHookFail()
	{
		$model = new TestModelHookFail( 5 );
		$this->assertFalse( $model->delete() );
	}
}

class TestModel extends Model
{
	static $properties = [
		'id' => [
			'type' => 'id'
		],
		'relation' => [
			'type' => 'id',
			'relation' => 'TestModel2',
			'null' => true
		],
		'answer' => [
			'type' => 'string'
		]
	];
	var $preDelete;
	var $postDelete;

	protected function hasPermission( $permission, Model $requester )
	{
		return true;
	}

	function preCreateHook()
	{
		$this->preCreate = true;
		return true;
	}

	function postCreateHook()
	{
		$this->postCreate = true;
	}

	function preSetHook()
	{
		$this->preSet = true;
		return true;
	}

	function postSetHook()
	{
		$this->postSet = true;
	}

	function preDeleteHook()
	{
		$this->preDelete = true;
		return true;
	}

	function postDeleteHook()
	{
		$this->postDelete = true;
	}
}

class TestModel2 extends Model
{
	static $properties = [
		'id' => [
			'type' => 'id',
			'mutable' => true
		],
		'id2' => [
			'type' => 'id',
			'mutable' => true
		],
		'default' => [
			'type' => 'text',
			'default' => 'some default value'
		],
		'validate' => [
			'type' => 'text',
			'validate' => 'email',
			'null' => true
		],
		'unique' => [
			'type' => 'text',
			'unique' => true
		],
		'required' => [
			'type' => 'text',
			'required' => true
		]
	];

	protected function hasPermission( $permission, Model $requester )
	{
		return true;
	}

	static function idProperty()
	{
		return [ 'id', 'id2' ];
	}

	static function hasSchema()
	{
		return false;
	}
}

class TestModelNoPermission extends Model
{
	protected function hasPermission( $permission, Model $requester )
	{
		return false;
	}
}

class TestModelHookFail extends Model
{
	protected function hasPermission( $permission, Model $requester )
	{
		return true;
	}

	function preCreateHook()
	{
		return false;
	}

	function preSetHook()
	{
		return false;
	}

	function preDeleteHook()
	{
		return false;
	}
}

class Person extends Model
{
	protected function hasPermission( $permission, Model $requester )
	{
		return false;
	}
}