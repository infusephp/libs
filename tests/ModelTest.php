<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2013 Jared King
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

		TestModel::configure( array(
			'database' => array(
				'enabled' => false ),
			'requester' => self::$requester ) );

		TestModel2::configure( array(
			'database' => array(
				'enabled' => false ),
			'requester' => self::$requester ) );
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

	function testGet()
	{

	}

	function testGetMultipleProperties()
	{

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

		$expected = array(
			'id' => 5,
			'relation' => 'test',
			'answer' => null
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

		$this->assertEquals( $expected, $model->toArray( array( 'relation', 'answer' ) ) );
	}

	function testToJson()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$this->assertEquals( '{"id":5,"relation":"test","answer":null}', $model->toJson() );
	}

	function testHasSchema()
	{
		$this->assertTrue( TestModel::hasSchema() );
		$this->assertFalse( TestModel2::hasSchema() );
	}

	function testCache()
	{
		$model = new TestModel( 3 );

		$model->cacheProperties( array(
			'test' => 123,
			'test2' => 'hello' ) );
		$this->assertEquals( 123, $model->test );
		$this->assertEquals( 'hello', $model->test2 );

		$model2 = new TestModel( 3 );
		$this->assertEquals( 123, $model2->test );
		$this->assertEquals( 'hello', $model2->test2 );
	}

	function testInvalidateCache()
	{
		$model = new testModel( 4 );
		// TODO
		$model->emptyCache();
		// TODO
	}

	function testCreate()
	{

	}

	function testCreateNoPermission()
	{
		$this->assertFalse( TestModelNoPermission::create( array() ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModelNoPermission.create' ) );
	}

	function testCreateHookFail()
	{
		$this->assertFalse( TestModelHookFail::create( array() ) );
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

		$this->assertTrue( $model->set( array(
			'answer' => 'hello',
			'relation' => 'anyone there?',
			'nonexistent property' => 'whatever' ) ) );
		$this->assertEquals( 'hello', $model->answer );
		$this->assertEquals( 'anyone there?', $model->relation );
	}

	function testSetNoPermission()
	{
		$model = new TestModelNoPermission( 5 );
		$this->assertFalse( $model->set( 'answer', 42 ) );
		$this->assertCount( 1, ErrorStack::stack()->errors( 'TestModelNoPermission.set' ) );
	}

	function testSetHookFail()
	{
		$model = new TestModelHookFail( 5 );
		$this->assertFalse( $model->set( 'answer', 42 ) );
	}

	function testDelete()
	{
		$model = new TestModel2( 1 );
		$this->assertTrue( $model->delete() );
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
	static $properties = array(
		'id' => array(
			'type' => 'id'
		),
		'relation' => array(
			'type' => 'id',
			'relation' => 'TestModel2'
		),
		'answer' => array(
			'type' => 'string'
		)
	);

	function can( $permission, AclRequester $requester )
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
	static $idProperty = array( 'id', 'id2' );
	static $properties = array(
		'id' => array(
			'type' => 'id'
		),
		'id2' => array(
			'type' => 'id'
		),
		'default' => array(
			'type' => 'text',
			'default' => 'some default value'
		)
	);
	static $hasSchema = false;

	function can( $permission, AclRequester $requester )
	{
		return true;
	}
}

class TestModelNoPermission extends Model
{
	function can( $permission, AclRequester $requester )
	{
		return false;
	}
}

class TestModelHookFail extends Model
{
	function can( $permission, AclRequester $requester )
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

class Person extends Model implements AclRequester
{
	public function groups( $owner )
	{
		return array();
	}
}