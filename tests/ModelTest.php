<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\ErrorStack;
use infuse\Locale;
use infuse\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    public static $requester;
    public static $app;

    public static function setUpBeforeClass()
    {
        self::$requester = new Person( 1 );

        Model::configure([
            'requester' => self::$requester ]);

        // set up DI
        self::$app = new \Pimple\Container();
        self::$app['locale'] = function () {
            return new Locale();
        };
        self::$app['errors'] = function ($app) {
            return new ErrorStack($app);
        };

        Model::inject( self::$app );
    }

    public function testConfigure()
    {
        TestModel::configure( [
            'test' => 123,
            'test2' => 12345 ] );

        $this->assertEquals( 123, TestModel::getConfigValue( 'test' ) );
        $this->assertEquals( 12345, TestModel::getConfigValue( 'test2' ) );
    }

    public function testInjectContainer()
    {
        $c = new \Pimple\Container();
        Model::inject( self::$app );
    }

    public function testProperties()
    {
        $expected = [
            'id' => [
                'type' => 'number',
                'mutable' => false,
                'admin_hidden_property' => true
            ],
            'relation' => [
                'type' => 'number',
                'relation' => 'TestModel2',
                'null' => true
            ],
            'answer' => [
                'type' => 'string'
            ],
            'test_hook' => [
                'type' => 'string',
                'null' => true
            ]
        ];

        $this->assertEquals( $expected, TestModel::properties() );
    }

    public function testPropertiesIdOverwrite()
    {
        $expected = [ 'type' => 'string' ];

        $this->assertEquals( $expected, Person::properties( 'id' ) );
    }

    public function testProperty()
    {
        $expected = [
            'type' => 'number',
            'mutable' => false,
            'admin_hidden_property' => true
        ];
        $this->assertEquals( $expected, TestModel::properties( 'id' ) );

        $expected = [
            'type' => 'number',
            'relation' => 'TestModel2',
            'null' => true
        ];
        $this->assertEquals( $expected, TestModel::properties( 'relation' ) );
    }

    public function testPropertiesAutoTimestamps()
    {
        $expected = [
            'id' => [
                'type' => 'number'
            ],
            'id2' => [
                'type' => 'number'
            ],
            'default' => [
                'type' => 'string',
                'default' => 'some default value'
            ],
            'validate' => [
                'type' => 'string',
                'validate' => 'email',
                'null' => true
            ],
            'unique' => [
                'type' => 'string',
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
                'type' => 'number',
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
                'default' => 'now',
                'admin_hidden_property' => true,
                'admin_type' => 'datepicker'
            ],
            'updated_at' => [
                'type' => 'date',
                'validate' => 'timestamp',
                'null' => true,
                'admin_hidden_property' => true,
                'admin_type' => 'datepicker'
            ]
        ];

        $this->assertEquals( $expected, TestModel2::properties() );
    }

    public function testId()
    {
        $model = new TestModel( 5 );

        $this->assertEquals( 5, $model->id() );
    }

    public function testMultipleIds()
    {
        $model = new TestModel2( [ 5, 2 ] );

        $this->assertEquals( '5,2', $model->id() );
    }

    public function testIdKeyValue()
    {
        $model = new TestModel( 3 );
        $this->assertEquals( [ 'id' => 3 ], $model->id( true ) );

        $model = new TestModel2( [ 5, 2 ] );
        $this->assertEquals( [ 'id' => 5, 'id2' => 2 ], $model->id( true ) );
    }

    public function testToString()
    {
        $model = new TestModel( 1 );
        $this->assertEquals( 'TestModel(1)', (string) $model );
    }

    public function testSetProperty()
    {
        $model = new TestModel( 2 );

        $model->test = 12345;
        $this->assertEquals( 12345, $model->test );

        $model->null = null;
        $this->assertEquals( null, $model->null );
    }

    public function testIsset()
    {
        $model = new TestModel( 1 );

        $this->assertFalse( isset( $model->test2 ) );

        $model->test = 12345;
        $this->assertTrue( isset( $model->test ) );

        $model->null = null;
        $this->assertTrue( isset( $model->null ) );
    }

    public function testUnset()
    {
        $model = new TestModel( 1 );

        $model->test = 12345;
        unset( $model->test );
        $this->assertFalse( isset( $model->test ) );
    }

    public function testInfo()
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

    public function testTablename()
    {
        $this->assertEquals( 'TestModels', TestModel::tablename() );
    }

    public function testHasNoId()
    {
        $model = new TestModel();
        $this->assertFalse( $model->id() );
    }

    public function testIsIdProperty()
    {
        $this->assertFalse( TestModel::isIdProperty( 'blah' ) );
        $this->assertTrue( TestModel::isIdProperty( 'id' ) );
        $this->assertTrue( TestModel2::isIdProperty( 'id2' ) );
    }

    public function testExists()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->scalar')->andReturn(1);

        $model = new TestModel2(12);
        $this->assertTrue($model->exists());

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->scalar')->andReturn(0);
        $this->assertFalse($model->exists());
    }

    public function testGetMultipleProperties()
    {
        $model = new TestModel( 3 );
        $model->relation = '10';
        $model->answer = 42;

        $expected = [
            'id' => 3,
            'relation' => 10,
            'answer' => 42 ];

        $values = $model->get( [ 'id', 'relation', 'answer' ] );
        $this->assertEquals( $expected, $values );
    }

    public function testGetFromDb()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['person' => 20]);

        $model = new TestModel2(12);
        $this->assertEquals(20, $model->person);
    }

    public function testGetDefaultValue()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel2( 12 );
        $this->assertEquals( 'some default value', $model->get( 'default' ) );
    }

    public function testRelation()
    {
        $model = new TestModel();
        $model->relation = 2;

        $relation = $model->relation( 'relation' );
        $this->assertInstanceOf( 'TestModel2', $relation );
        $this->assertEquals( 2, $relation->id() );

        // test if relation model is cached
        $relation->test = 'hello';
        $relation2 = $model->relation( 'relation' );
        $this->assertEquals( 'hello', $relation2->test );
    }

    public function testToArray()
    {
        $model = new TestModel( 5 );
        $model->relation = '10';

        $expected = [
            'id' => 5,
            'relation' => 10,
            'answer' => null,
            'test_hook' => null,
            // this is tacked on in toArrayHook() below
            'toArray' => true
        ];

        $this->assertEquals( $expected, $model->toArray() );
    }

    public function testToArrayExcluded()
    {
        $model = new TestModel( 5 );
        $model->relation = 100;

        $expected = [
            'relation' => 100
        ];

        $this->assertEquals( $expected, $model->toArray( [ 'id', 'answer', 'toArray', 'test_hook' ] ) );
    }

    public function testToArrayAutoTimestamps()
    {
        $model = new TestModel2( 5 );
        $model->created_at = 100;
        $model->updated_at = 102;

        $expected = [ 'created_at' => 100, 'updated_at' => 102 ];

        $this->assertEquals( $expected, $model->toArray( [ 'id', 'id2', 'default', 'validate', 'unique', 'required' ] ) );
    }

    public function testToArrayIncluded()
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

    public function testToArrayExpand()
    {
        $model = new TestModel( 10 );
        $model->relation = 100;
        $model->answer = 42;

        $result = $model->toArray(
            [
                'id',
                'toArray',
                'test_hook',
                'relation.created_at',
                'relation.updated_at',
                'relation.validate',
                'relation.unique',
                'relation.person.address' ],
            [
                'relation.hidden',
                'relation.person' ],
            [
                'relation.person',
                'answer' ] );

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

    public function testToJson()
    {
        $model = new TestModel( 5 );
        $model->relation = '10';

        $this->assertEquals( '{"id":5,"test_hook":null,"relation":10,"answer":null}', $model->toJson( [ 'toArray' ] ) );
    }

    public function testHasSchema()
    {
        $this->assertTrue( TestModel::hasSchema() );
        $this->assertFalse( TestModel2::hasSchema() );
    }

    public function testCacheAndValueMarshaling()
    {
        $model = new TestModel2( 3 );

        $json = [
            'test' => true,
            'test2' => [
                'hello',
                'anyone there?' ] ];

        $this->assertEquals($model, $model->cacheProperties( [
            'validate' => '',
            'hidden' => '1',
            'default' => 'testing',
            'test2' => 'hello',
            'person' => '30',
            'required' => '50',
            'json' => $json ] ));

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

    public function testInvalidateCache()
    {
        $model = new testModel( 4 );

        $model->answer = 42;
        $model->test = 1234;
        $this->assertTrue( isset( $model->answer ) );
        $this->assertTrue( isset( $model->test ) );

        $this->assertEquals($model, $model->emptyCache());

        $this->assertNotEquals( 42, $model->answer );
        $this->assertFalse( isset( $model->test ) );
    }

    public function testCreate()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);
        self::$app['pdo'] = Mockery::mock();
        self::$app['pdo']->shouldReceive('lastInsertId')->andReturn(1);

        $newModel = new TestModel();
        $this->assertTrue( $newModel->create( [ 'relation' => '', 'answer' => 42 ] ) );
        $this->assertEquals(1, $newModel->id());
        $this->assertEquals(1, $newModel->id);
        $this->assertEquals( null, $newModel->relation );
        $this->assertEquals( 42, $newModel->answer );
    }

    public function testCreateMutable()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $newModel = new TestModel2();
        $this->assertTrue( $newModel->create( [ 'id' => 1, 'id2' => 2, 'required' => 25 ] ) );
        $this->assertEquals( '1,2', $newModel->id() );
    }

    public function testCreateJson()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $json = [ 'test' => true, 'test2' => [ 1, 2, 3 ] ];

        $newModel = new TestModel2();
        $this->assertTrue( $newModel->create( [ 'id' => 2, 'id2' => 4, 'required' => 25, 'json' => $json ] ) );
        $this->assertEquals( $json, $newModel->json );
    }

    public function testCreateAutoTimestamps()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $newModel = new TestModel2();
        $this->assertTrue( $newModel->create( [ 'id' => 1, 'id2' => 2, 'required' => 235 ] ) );
        $this->assertGreaterThan( 0, $newModel->created_at );
    }

    public function testCreateWithId()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $model = new TestModel( 5 );
        $this->assertFalse( $model->create( [ 'relation' => '', 'answer' => 42 ] ) );
    }

    public function testCreateNoPermission()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $newModel = new TestModelNoPermission();
        $this->assertFalse( $newModel->create( [] ) );
        $this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.create' ) );
    }

    public function testCreateHookFail()
    {
        $newModel = new TestModelHookFail();
        $this->assertFalse( $newModel->create( [] ) );
    }

    public function testCreateNotUnique()
    {
        // TODO
    }

    public function testCreateInvalid()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $newModel = new TestModel2();
        $this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true ] ) );
        $this->assertCount( 1, $errorStack->errors( 'TestModel2.create' ) );
    }

    public function testCreateMissingRequired()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $newModel = new TestModel2();
        $this->assertFalse( $newModel->create( [ 'id' => 10, 'id2' => 1 ] ) );
        $this->assertCount( 1, $errorStack->errors( 'TestModel2.create' ) );
    }

    public function testToArrayAfterCreate()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel2();
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

    public function testSet()
    {
        $stmt = Mockery::mock('PDOStatement');

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn($stmt);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel(10);

        $this->assertTrue($model->set([]));
        $this->assertTrue($model->set('answer', 42));
        $this->assertEquals(42, $model->answer);
    }

    public function testSetMultiple()
    {
        $stmt = Mockery::mock('PDOStatement');

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn($stmt);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel( 11 );

        $this->assertTrue( $model->set( [
            'answer' => 'hello',
            'relation' => '',
            'nonexistent_property' => 'whatever' ] ) );
        $this->assertEquals( 'hello', $model->answer );
        $this->assertEquals( null, $model->relation );
    }

    public function testSetAutoTimestamps()
    {
        $stmt = Mockery::mock('PDOStatement');

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn($stmt);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel2(12);
        $updatedAt = $model->updated_at;
        $model->set('default', 'testing');
        $this->assertNotEquals($updatedAt, $model->updated_at);
    }

    public function testSetJson()
    {
        $stmt = Mockery::mock('PDOStatement');

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn($stmt);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $json = [ 'test' => true, 'test2' => [ 1, 2, 3 ] ];

        $model = new TestModel2( 13 );
        $model->set( 'json', $json );
        $this->assertEquals( $json, $model->json );
    }

    public function testSetImmutableProperties()
    {
        $stmt = Mockery::mock('PDOStatement');

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn($stmt);
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel(10);

        $this->assertTrue($model->set('id', 432));
        $this->assertEquals(10, $model->id);
    }

    public function testSetFailWithNoId()
    {
        $model = new TestModel();
        $this->assertFalse( $model->set( [ 'answer' => 42 ] ) );
    }

    public function testSetNoPermission()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $model = new TestModelNoPermission( 5 );
        $this->assertFalse( $model->set( 'answer', 42 ) );
        $this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.set' ) );
    }

    public function testSetHookFail()
    {
        $model = new TestModelHookFail( 5 );
        $this->assertFalse( $model->set( 'answer', 42 ) );
    }

    public function testSetNotUnique()
    {
        // TODO
    }

    public function testSetInvalid()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $model = new TestModel2( 15 );

        $this->assertFalse( $model->set( 'validate', 'not a valid email' ) );
        $this->assertCount( 1, $errorStack->errors( 'TestModel2.set' ) );
    }

    public function testDelete()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('delete->where->execute')->andReturn(true);

        $model = new TestModel2( 1 );
        $this->assertTrue( $model->delete() );
    }

    public function testDeleteWithNoId()
    {
        $model = new TestModel();
        $this->assertFalse( $model->delete() );
    }

    public function testDeleteWithHook()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('delete->where->execute')->andReturn(true);

        $model = new TestModel( 100 );

        $this->assertTrue( $model->delete() );
        $this->assertTrue( $model->preDelete );
        $this->assertTrue( $model->postDelete );
    }

    public function testDeleteNoPermission()
    {
        $errorStack = self::$app[ 'errors' ];
        $model = new TestModelNoPermission( 5 );
        $this->assertFalse( $model->delete() );
        $this->assertCount( 1, $errorStack->errors( 'TestModelNoPermission.delete' ) );
    }

    public function testDeleteHookFail()
    {
        $model = new TestModelHookFail( 5 );
        $this->assertFalse( $model->delete() );
    }
}

class TestModel extends Model
{
    static $properties = [
        'relation' => [
            'type' => 'number',
            'relation' => 'TestModel2',
            'null' => true
        ],
        'answer' => [
            'type' => 'string'
        ]
    ];
    public $preDelete;
    public $postDelete;

    protected static function propertiesHook()
    {
        $properties = parent::propertiesHook();

        $properties[ 'test_hook' ] = [
            'type' => 'string',
            'null' => true ];

        return $properties;
    }

    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function preCreateHook()
    {
        $this->preCreate = true;

        return true;
    }

    public function postCreateHook()
    {
        $this->postCreate = true;
    }

    public function preSetHook()
    {
        $this->preSet = true;

        return true;
    }

    public function postSetHook()
    {
        $this->postSet = true;
    }

    public function preDeleteHook()
    {
        $this->preDelete = true;

        return true;
    }

    public function postDeleteHook()
    {
        $this->postDelete = true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if( !isset( $exclude[ 'toArray' ] ) )
            $result[ 'toArray' ] = true;
    }
}

class TestModel2 extends Model
{
    static $properties = [
        'id' => [
            'type' => 'number'
        ],
        'id2' => [
            'type' => 'number'
        ],
        'default' => [
            'type' => 'string',
            'default' => 'some default value'
        ],
        'validate' => [
            'type' => 'string',
            'validate' => 'email',
            'null' => true
        ],
        'unique' => [
            'type' => 'string',
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
            'type' => 'number',
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

    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if( isset( $include[ 'toArrayHook' ] ) )
            $result[ 'toArrayHook' ] = true;
    }

    public static function idProperty()
    {
        return [ 'id', 'id2' ];
    }

    public static function hasSchema()
    {
        return false;
    }
}

class TestModelNoPermission extends Model
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class TestModelHookFail extends Model
{
    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function preCreateHook()
    {
        return false;
    }

    public function preSetHook()
    {
        return false;
    }

    public function preDeleteHook()
    {
        return false;
    }
}

class Person extends Model
{
    static $properties = [
        'id' => [
            'type' => 'string'
        ],
        'name' => [
            'type' => 'string',
            'default' => 'Jared'
        ],
        'address' => [
            'type' => 'string'
        ]
    ];

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}
