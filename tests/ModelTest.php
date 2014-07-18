<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\AclRequester;
use infuse\ErrorStack;
use infuse\Locale;
use infuse\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
	static $requester;
	static $app;

	static function setUpBeforeClass()
	{
		self::$requester = new Person( 1 );

		Model::configure( [
			'database' => [
				'enabled' => false ],
			'requester' => self::$requester ] );

		// set up DI
		self::$app = new \Pimple\Container;
		self::$app[ 'locale' ] = function() {
			return new Locale;
		};
		self::$app[ 'errors' ] = function( $app ) {
			return new ErrorStack( $app );
		};
		Model::inject( self::$app );
	}

	function testConfigure()
	{
		TestModel::configure( [
			'test' => 123,
			'test2' => 12345 ] );

		$this->assertEquals( 123, TestModel::getConfigValue( 'test' ) );
		$this->assertEquals( 12345, TestModel::getConfigValue( 'test2' ) );
	}

	function testInjectContainer()
	{
		$c = new \Pimple\Container;
		Model::inject( self::$app );
	}

	function testProperties()
	{
		$expected = [
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

		$this->assertEquals( $expected, TestModel::properties() );

		$this->assertEquals( [ 'type' => 'id' ], TestModel::properties( 'id' ) );
	}

	function testPropertiesAutoTimestamps()
	{
		$expected = [
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
				'type' => 'number',
				'required' => true
			],
			'hidden' => [
				'type' => 'boolean',
				'default' => false,
				'hidden' => true
			],
			'person' => [
				'type' => 'id',
				'relation' => 'Person',
				'default' => 20,
				'hidden' => true
			],
			'json' => [
				'type' => 'json',
				'hidden' => true,
				'default' => '{"tax":"%","discounts":false,"shipping":false}'
			],
			'created_at' => [
				'type' => 'date',
				'validate' => 'timestamp',
				'required' => true,
				'default' => 'today'
			],
			'updated_at' => [
				'type' => 'date',
				'validate' => 'timestamp',
				'null' => true,
			]
		];

		$this->assertEquals( $expected, TestModel2::properties() );
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
		$model->relation = 'test';
		$model->answer = 42;

		$expected = [
			'id' => 3,
			'relation' => 0,
			'answer' => 42 ];

		$values = $model->get( [ 'id', 'relation', 'answer' ] );
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
			'relation' => 0,
			'answer' => null,
			// this is tacked on in toArrayHook() below
			'toArray' => true
		];

		$this->assertEquals( $expected, $model->toArray() );
	}

	function testToArrayExcluded()
	{
		$model = new TestModel( 5 );
		$model->relation = 100;

		$expected = [
			'relation' => 100
		];

		$this->assertEquals( $expected, $model->toArray( [ 'id', 'answer', 'toArray' ] ) );
	}

	function testToArrayAutoTimestamps()
	{
		$model = new TestModel2( 5 );
		$model->created_at = 100;
		$model->updated_at = 102;

		$expected = [ 'created_at' => 100, 'updated_at' => 102 ];

		$this->assertEquals( $expected, $model->toArray( [ 'id', 'id2', 'default', 'validate', 'unique', 'required' ] ) );
	}

	function testToArrayIncluded()
	{
		$model = new TestModel2( 5 );
		$model->hidden = true;

		$expected = [
			'hidden' => true,
			'json' => [
				'tax' => '%',
				'discounts' => false,
				'shipping' => false ],
			'toArrayHook' => true ];

		$this->assertEquals( $expected, $model->toArray( [ 'id', 'id2', 'default', 'validate', 'unique', 'required', 'created_at', 'updated_at' ], [ 'hidden', 'toArrayHook', 'json' ] ) );
	}

	function testToArrayExpand()
	{
		$model = new TestModel( 10 );
		$model->relation = 100;
		$model->answer = 42;

		$result = $model->toArray(
			[
				'id',
				'toArray',
				'relation.created_at',
				'relation.updated_at',
				'relation.validate',
				'relation.unique',
				'relation.person.address' ],
			[
				'relation.hidden',
				'relation.person' ],
			[
				'relation.person' ] );

		$expected = [
			'answer' => 42,
			'relation' => [
				'id' => 100,
				'id2' => 0,
				'required' => null,
				'default' => 'some default value',
				'hidden' => false,
				'person' => [
					'id' => 20,
					'name' => 'Jared'
				]
			]
		];

		$this->assertEquals( $expected, $result );
	}

	function testToJson()
	{
		$model = new TestModel( 5 );
		$model->relation = 'test';

		$this->assertEquals( '{"id":5,"relation":0,"answer":null}', $model->toJson( [ 'toArray' ] ) );
	}

	function testHasSchema()
	{
		$this->assertTrue( TestModel::hasSchema() );
		$this->assertFalse( TestModel2::hasSchema() );
	}

	function testCacheAndValueMarshaling()
	{
		$model = new TestModel2( 3 );

		$json = [
			'test' => true,
			'test2' => [
				'hello',
				'anyone there?' ] ];

		$model->cacheProperties( [
			'validate' => '',
			'hidden' => '1',
			'default' => 'testing',
			'test2' => 'hello',
			'person' => '30',
			'required' => '50',
			'json' => $json ] );
		$this->assertEquals( '', $model->validate );
		$this->assertEquals( '1', $model->hidden );
		$this->assertEquals( '50', $model->required );
		$this->assertEquals( '30', $model->person );
		$this->assertEquals( 'hello', $model->test2 );
		$this->assertEquals( 'testing', $model->default );
		$this->assertEquals( $json, $model->json );

		$model2 = new TestModel2( 3 );
		$this->assertTrue( $model2->validate === null );
		$this->assertTrue( $model2->hidden === true );
		$this->assertTrue( $model2->required === 50 );
		$this->assertTrue( $model2->person === 30 );
		$this->assertTrue( $model2->default === 'testing' );
		$this->assertEquals( 'hello', $model2->test2 );
		$this->assertEquals( $json, $model2->json );
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
		$this->assertTrue( !empty( $newModel->id ) );
		$this->assertEquals( null, $newModel->relation );
		$this->assertEquals( 42, $newModel->answer );
	}

	function testCreateMutable()
	{
		$newModel = new TestModel2;
		$this->assertTrue( $newModel->create( [ 'id' => 1, 'id2' => 2, 'required' => 25 ] ) );
		$this->assertEquals( '1,2', $newModel->id() );
	}

	function testCreateJson()
	{
		$json = [ 'test' => true, 'test2' => [ 1, 2, 3 ] ];

		$newModel = new TestModel2;
		$this->assertTrue( $newModel->create( [ 'id' => 2, 'id2' => 4, 'required' => 25, 'json' => $json ] ) );
		$this->assertEquals( $json, $newModel->json );
	}

	function testCreateAutoTimestamps()
	{
		$newModel = new TestModel2;
		$this->assertTrue( $newModel->create( [ 'id' => 1, 'id2' => 2, 'required' => 235 ] ) );
		$this->assertGreaterThan( 0, $newModel->created_at );
	}

	function testCreateWithId()
	{
		$model = new TestModel( 5 );
		$this->assertFalse( $model->create( [ 'relation' => '', 'answer' => 42 ] ) );
	}

	function testCreateNoPermission()
	{
		$errorStack = self::$app[ 'errors' ];
		$errorStack->clear();
		$newModel = new TestModelNoPermission;
		$this->assertFalse( $newModel->create( [] ) );
		$this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.create' ) );
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
		$errorStack = self::$app[ 'errors' ];
		$errorStack->clear();
		$newModel = new TestModel2;
		$this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true ] ) );
		$this->assertCount( 1, $errorStack->errors( 'TestModel2.create' ) );
	}

	function testCreateMissingRequired()
	{
		$errorStack = self::$app[ 'errors' ];
		$errorStack->clear();
		$newModel = new TestModel2;
		$this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1 ] ) );
		$this->assertCount( 1, $errorStack->errors( 'TestModel2.create' ) );
	}

	function testToArrayAfterCreate()
	{
		$model = new TestModel2;
		$this->assertTrue( $model->create( [
			'id' => 5,
			'id2' => 10,
			'required' => true ] ) );

		$expected = [
			'id' => 5,
			'id2' => 10,
			'required' => true,
			'default' => 'some default value',
			'validate' => null,
			'unique' => null,
			'updated_at' => null
		];

		$this->assertEquals( $expected, $model->toArray( [ 'created_at' ] ) );
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

	function testSetAutoTimestamps()
	{
		$model = new TestModel2( 12 );
		$updatedAt = $model->updated_at;
		$model->set( 'default', 'testing' );
		$this->assertNotEquals( $updatedAt, $model->updated_at );
	}

	function testSetJson()
	{
		$json = [ 'test' => true, 'test2' => [ 1, 2, 3 ] ];

		$model = new TestModel2( 13 );
		$model->set( 'json', $json );
		$this->assertEquals( $json, $model->json );
	}

	function testSetFailWithNoId()
	{
		$model = new TestModel;
		$this->assertFalse( $model->set( [ 'answer' => 42 ] ) );
	}

	function testSetNoPermission()
	{
		$errorStack = self::$app[ 'errors' ];
		$errorStack->clear();
		$model = new TestModelNoPermission( 5 );
		$this->assertFalse( $model->set( 'answer', 42 ) );
		$this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.set' ) );
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
		$errorStack = self::$app[ 'errors' ];
		$errorStack->clear();
		$model = new TestModel2( 15 );

		$this->assertFalse( $model->set( 'validate', 'not a valid email' ) );
		$this->assertCount( 1, $errorStack->errors( 'TestModel2.set' ) );
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
		$errorStack = self::$app[ 'errors' ];
		$model = new TestModelNoPermission( 5 );
		$this->assertFalse( $model->delete() );
		$this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.delete' ) );
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

	function toArrayHook( array &$result, array $exclude, array $include, array $expand )
	{
		if( !isset( $exclude[ 'toArray' ] ) )
			$result[ 'toArray' ] = true;
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
			'type' => 'number',
			'required' => true
		],
		'hidden' => [
			'type' => 'boolean',
			'default' => false,
			'hidden' => true
		],
		'person' => [
			'type' => 'id',
			'relation' => 'Person',
			'default' => 20,
			'hidden' => true
		],
		'json' => [
			'type' => 'json',
			'default' => '{"tax":"%","discounts":false,"shipping":false}',
			'hidden' => true
		]
	];

	public static $autoTimestamps;

	protected function hasPermission( $permission, Model $requester )
	{
		return true;
	}

	function toArrayHook( array &$result, array $exclude, array $include, array $expand )
	{
		if( isset( $include[ 'toArrayHook' ] ) )
			$result[ 'toArrayHook' ] = true;
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
	static $properties = [
		'id' => [
			'type' => 'id'
		],
		'name' => [
			'type' => 'text',
			'default' => 'Jared'
		],
		'address' => [
			'type' => 'text'
		]
	];

	protected function hasPermission( $permission, Model $requester )
	{
		return false;
	}
}