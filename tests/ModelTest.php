<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
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
        self::$requester = new Person(1);

        Model::configure([
            'requester' => self::$requester, ]);

        // set up DI
        self::$app = new \Pimple\Container();
        self::$app['locale'] = function () {
            return new Locale();
        };
        self::$app['errors'] = function ($app) {
            return new ErrorStack($app);
        };

        Model::inject(self::$app);
    }

    public function testConfigure()
    {
        TestModel::configure([
            'test' => 123,
            'test2' => 12345, ]);

        $this->assertEquals(123, TestModel::getConfigValue('test'));
        $this->assertEquals(12345, TestModel::getConfigValue('test2'));
    }

    public function testInjectContainer()
    {
        $c = new \Pimple\Container();
        Model::inject(self::$app);
    }

    public function testProperties()
    {
        $expected = [
            'id' => [
                'type' => Model::TYPE_NUMBER,
                'mutable' => Model::IMMUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
                'admin_hidden_property' => true,
            ],
            'relation' => [
                'type' => Model::TYPE_NUMBER,
                'relation' => 'TestModel2',
                'null' => true,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'mutable' => Model::MUTABLE,
                'hidden' => false,
            ],
            'answer' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'test_hook' => [
                'type' => Model::TYPE_STRING,
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
        ];

        $this->assertEquals($expected, TestModel::properties());
    }

    public function testPropertiesIdOverwrite()
    {
        $expected = [
            'type' => Model::TYPE_STRING,
            'mutable' => Model::MUTABLE,
            'null' => false,
            'unique' => false,
            'required' => false,
            'searchable' => false,
            'hidden' => false,
        ];

        $this->assertEquals($expected, Person::properties('id'));
    }

    public function testProperty()
    {
        $expected = [
            'type' => Model::TYPE_NUMBER,
            'mutable' => Model::IMMUTABLE,
            'null' => false,
            'unique' => false,
            'required' => false,
            'searchable' => false,
            'admin_hidden_property' => true,
            'hidden' => false,
        ];
        $this->assertEquals($expected, TestModel::properties('id'));

        $expected = [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'TestModel2',
            'null' => true,
            'unique' => false,
            'required' => false,
            'searchable' => false,
            'mutable' => Model::MUTABLE,
            'hidden' => false,
        ];
        $this->assertEquals($expected, TestModel::properties('relation'));
    }

    public function testPropertiesAutoTimestamps()
    {
        $expected = [
            'id' => [
                'type' => Model::TYPE_NUMBER,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'id2' => [
                'type' => Model::TYPE_NUMBER,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'default' => [
                'type' => Model::TYPE_STRING,
                'default' => 'some default value',
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'validate' => [
                'type' => Model::TYPE_STRING,
                'validate' => 'email',
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'validate2' => [
                'type' => Model::TYPE_STRING,
                'validate' => 'validate',
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => true,
            ],
            'unique' => [
                'type' => Model::TYPE_STRING,
                'unique' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'required' => [
                'type' => Model::TYPE_NUMBER,
                'required' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'searchable' => false,
                'hidden' => false,
            ],
            'hidden' => [
                'type' => Model::TYPE_BOOLEAN,
                'default' => false,
                'hidden' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
            ],
            'person' => [
                'type' => Model::TYPE_NUMBER,
                'relation' => 'Person',
                'default' => 20,
                'hidden' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
            ],
            'json' => [
                'type' => Model::TYPE_JSON,
                'hidden' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'default' => '{"tax":"%","discounts":false,"shipping":false}',
                'unique' => false,
                'required' => false,
                'searchable' => false,
            ],
            'mutable_create_only' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE_CREATE_ONLY,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => true,
            ],
            'created_at' => [
                'type' => Model::TYPE_DATE,
                'default' => null,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'admin_hidden_property' => true,
                'admin_type' => 'datepicker',
                'hidden' => false,
            ],
            'updated_at' => [
                'type' => Model::TYPE_DATE,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'searchable' => false,
                'hidden' => false,
                'admin_hidden_property' => true,
                'admin_type' => 'datepicker',
            ],
        ];

        $this->assertEquals($expected, TestModel2::properties());
    }

    public function testId()
    {
        $model = new TestModel(5);

        $this->assertEquals(5, $model->id());
    }

    public function testMultipleIds()
    {
        $model = new TestModel2([ 5, 2 ]);

        $this->assertEquals('5,2', $model->id());
    }

    public function testIdKeyValue()
    {
        $model = new TestModel(3);
        $this->assertEquals([ 'id' => 3 ], $model->id(true));

        $model = new TestModel2([ 5, 2 ]);
        $this->assertEquals([ 'id' => 5, 'id2' => 2 ], $model->id(true));
    }

    public function testToString()
    {
        $model = new TestModel(1);
        $this->assertEquals('TestModel(1)', (string) $model);
    }

    public function testSetUnsaved()
    {
        $model = new TestModel(2);

        $model->test = 12345;
        $this->assertEquals(12345, $model->test);

        $model->null = null;
        $this->assertEquals(null, $model->null);
    }

    public function testIsset()
    {
        $model = new TestModel(1);

        $this->assertFalse(isset($model->test2));

        $model->test = 12345;
        $this->assertTrue(isset($model->test));

        $model->null = null;
        $this->assertTrue(isset($model->null));
    }

    public function testUnset()
    {
        $model = new TestModel(1);

        $model->test = 12345;
        unset($model->test);
        $this->assertFalse(isset($model->test));
    }

    public function testInfo()
    {
        $expected = [
            'model' => 'TestModel',
            'class_name' => 'TestModel',
            'singular_key' => 'test_model',
            'plural_key' => 'test_models',
            'proper_name' => 'Test Model',
            'proper_name_plural' => 'Test Models', ];

        $this->assertEquals($expected, TestModel::info());
    }

    public function testTablename()
    {
        $this->assertEquals('TestModels', TestModel::tablename());
    }

    public function testHasNoId()
    {
        $model = new TestModel();
        $this->assertFalse($model->id());
    }

    public function testIsIdProperty()
    {
        $this->assertFalse(TestModel::isIdProperty('blah'));
        $this->assertTrue(TestModel::isIdProperty('id'));
        $this->assertTrue(TestModel2::isIdProperty('id2'));
    }

    public function testTotalRecords()
    {
        // select query mock
        $scalar = Mockery::mock();
        $scalar->shouldReceive('scalar')->andReturn(1);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([[]])->andReturn($scalar);
        $from = Mockery::mock();
        $from->shouldReceive('from')->withArgs(['TestModel2s'])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select')->withArgs(['count(*)'])->andReturn($from);

        $model = new TestModel2(12);
        $this->assertEquals(1, $model->totalRecords());
    }

    public function testTotalRecordsFail()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->scalar')->andThrow(new Exception());
        self::$app['logger'] = Mockery::mock();
        self::$app['logger']->shouldReceive('error');

        $model = new TestModel2(12);
        $this->assertEquals(0, $model->totalRecords());
    }

    public function testExists()
    {
        // select query mock
        $scalar = Mockery::mock();
        $scalar->shouldReceive('scalar')->andReturn(1);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['id' => 12, 'id2' => null]])->andReturn($scalar);
        $from = Mockery::mock();
        $from->shouldReceive('from')->withArgs(['TestModel2s'])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select')->withArgs(['count(*)'])->andReturn($from);

        $model = new TestModel2(12);
        $this->assertTrue($model->exists());

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->scalar')->andReturn(0);
        $this->assertFalse($model->exists());
    }

    public function testGetMultipleProperties()
    {
        $model = new TestModel(3);
        $model->relation = '10';
        $model->answer = 42;

        $expected = [
            'id' => 3,
            'relation' => 10,
            'answer' => 42, ];

        $values = $model->get(['id', 'relation', 'answer']);
        $this->assertEquals($expected, $values);
    }

    public function testGetFromDb()
    {
        // select query mock
        $one = Mockery::mock();
        $one->shouldReceive('one')->andReturn(['answer' => 20]);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['id' => 12]])->andReturn($one);
        $from = Mockery::mock();
        $from->shouldReceive('from')->withArgs(['TestModels'])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select')->andReturn($from);

        $model = new TestModel(12);
        $this->assertEquals(20, $model->answer);
    }

    public function testGetDefaultValue()
    {
        // select query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel2(12);
        $this->assertEquals('some default value', $model->get('default'));
    }

    public function testMarshalBoolean()
    {
        // select query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['hidden' => '1']);

        $model = new TestModel2(12);
        $this->assertTrue($model->hidden);
    }

    public function testMarshalTimestamp()
    {
        // select query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['updated_at' => '2012-04-18 23:38:18']);

        $model = new TestModel2(12);
        $this->assertTrue(is_integer($model->updated_at));
        $this->assertGreaterThan(0, $model->updated_at);
    }

    public function testMarshalTimestampInteger()
    {
        // select query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['updated_at' => '123']);

        $model = new TestModel2(12);
        $this->assertTrue(is_integer($model->updated_at));
        $this->assertEquals(123, $model->updated_at);
    }

    public function testMarshalJson()
    {
        // select query mock
        $json = ['test' => true, 'test2' => [1, 2, 3]];
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['json' => json_encode($json)]);

        $model = new TestModel2(12);
        $this->assertEquals($json, $model->json);
    }

    // public function testGetEmptyProperty()
    // {
    //     // select mock
    //     self::$app['db'] = Mockery::mock();
    //     self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['' => 'blah']);

    //     $model = new TestModel(12);
    //     $this->assertEquals('blah', $model->get('whatever'));
    // }

    public function testRelation()
    {
        // select query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel();
        $model->relation = 2;

        $relation = $model->relation('relation');
        $this->assertInstanceOf('TestModel2', $relation);
        $this->assertEquals(2, $relation->id());

        // test if relation model is cached
        $relation->test = 'hello';
        $relation2 = $model->relation('relation');
        $this->assertEquals('hello', $relation2->test);

        // reset the relation
        $model->relation = 3;
        $this->assertEquals(3, $model->relation('relation')->id());

        // check other methods for thorougness...
        unset($model->relation);
        $model->relation = 4;
        $this->assertEquals(4, $model->relation('relation')->id());
    }

    public function testToArray()
    {
        $model = new TestModel(5);

        $expected = [
            'id' => 5,
            'relation' => null,
            'answer' => null,
            'test_hook' => null,
            // this is tacked on in toArrayHook() below
            'toArray' => true,
        ];

        $this->assertEquals($expected, $model->toArray([], [], ['relation']));
    }

    public function testToArrayExcluded()
    {
        $model = new TestModel(5);
        $model->relation = 100;

        $expected = [
            'relation' => 100,
        ];

        $this->assertEquals($expected, $model->toArray([ 'id', 'answer', 'toArray', 'test_hook' ]));
    }

    public function testToArrayAutoTimestamps()
    {
        $model = new TestModel2(5);
        $model->created_at = 100;
        $model->updated_at = 102;

        $expected = ['created_at' => 100, 'updated_at' => '102'];

        $this->assertEquals($expected, $model->toArray([ 'id', 'id2', 'default', 'validate', 'unique', 'required' ]));

        $model->created_at = '-1';
        $this->assertEquals(-1, $model->created_at);
    }

    public function testToArrayIncluded()
    {
        $model = new TestModel2(5);
        $model->hidden = true;

        $expected = [
            'hidden' => true,
            'json' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false, ],
            'toArrayHook' => true, ];

        $this->assertEquals($expected, $model->toArray([ 'id', 'id2', 'default', 'validate', 'unique', 'required', 'created_at', 'updated_at' ], [ 'hidden', 'toArrayHook', 'json' ]));
    }

    public function testToArrayExpand()
    {
        $model = new TestModel(10);
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
                'relation.person.address', ],
            [
                'relation.hidden',
                'relation.person' ],
            [
                'relation.person',
                'answer' ]);

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
                    'name' => 'Jared',
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToJson()
    {
        $model = new TestModel(5);
        $model->relation = 10;

        $this->assertEquals('{"id":"5","test_hook":null,"relation":10,"answer":null}', $model->toJson(['toArray']));
    }

    public function testHasSchema()
    {
        $this->assertTrue(TestModel::hasSchema());
        $this->assertFalse(TestModel2::hasSchema());
    }

    /////////////////////////////
    // CREATE
    /////////////////////////////

    public function testCreate()
    {
        // insert mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        // lastInsertId mock
        self::$app['pdo'] = Mockery::mock();
        self::$app['pdo']->shouldReceive('lastInsertId')->andReturn(1);

        // select mock
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([
            'id' => 1,
            'relation' => null,
            'answer' => 42, ]);

        $newModel = new TestModel();
        $this->assertTrue($newModel->create(['relation' => '', 'answer' => 42, 'extra' => true]));
        $this->assertEquals(1, $newModel->id());
        $this->assertEquals(1, $newModel->id);
        $this->assertEquals(null, $newModel->relation);
        $this->assertEquals(42, $newModel->answer);
    }

    public function testCreateMutable()
    {
        // insert mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $newModel = new TestModel2();
        $this->assertTrue($newModel->create(['id' => 1, 'id2' => 2, 'required' => 25]));
        $this->assertEquals('1,2', $newModel->id());
    }

    public function testCreateImmutable()
    {
        // insert mock
        $into = Mockery::mock();
        $into->shouldReceive('into->execute')->andReturn(true);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert')->withArgs([[
            'id' => 1,
            'id2' => 2,
            'required' => 25,
            'default' => 'some default value',
            'hidden' => false,
            'created_at' => null,
            'json' => '{"tax":"%","discounts":false,"shipping":false}',
            'person' => 20,
            'mutable_create_only' => 'test', ]])
            ->andReturn($into);

        // select mock
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['mutable_create_only' => 'test']);

        $newModel = new TestModel2();
        $this->assertTrue($newModel->create(['id' => 1, 'id2' => 2, 'required' => 25, 'mutable_create_only' => 'test']));
        $this->assertEquals('test', $newModel->mutable_create_only);
    }

    public function testCreateImmutableId()
    {
        // insert mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $newModel = new TestModel();
        $this->assertTrue($newModel->create(['id' => 100]));
        $this->assertNotEquals(100, $newModel->id());
    }

    public function testCreateJson()
    {
        $json = ['test' => true, 'test2' => [1, 2, 3]];

        // insert query mock
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')->andReturn(true);
        $into = Mockery::mock();
        $into->shouldReceive('into')->withArgs(['TestModel2s'])->andReturn($execute);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert')->withArgs([[
            'id' => 2,
            'id2' => 4,
            'required' => 25,
            'created_at' => null,
            'default' => 'some default value',
            'hidden' => false,
            'person' => 20,
            'json' => json_encode($json), ]])->andReturn($into);

        $newModel = new TestModel2();
        $this->assertTrue($newModel->create(['id' => 2, 'id2' => 4, 'required' => 25, 'json' => $json]));
    }

    public function testCreateWithId()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andReturn(true);

        $model = new TestModel(5);
        $this->assertFalse($model->create([ 'relation' => '', 'answer' => 42 ]));
    }

    public function testCreateNoPermission()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $newModel = new TestModelNoPermission();
        $this->assertFalse($newModel->create([]));
        $this->assertCount(1, $errorStack->errors('TestModelNoPermission.create'));
    }

    public function testCreateHookFail()
    {
        $newModel = new TestModelHookFail();
        $this->assertFalse($newModel->create([]));
    }

    public function testCreateNotUnique()
    {
        $errorStack = self::$app['errors'];
        $errorStack->clear();

        // select query mock
        $scalar = Mockery::mock();
        $scalar->shouldReceive('scalar')->andReturn(1);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['unique' => 'fail']])->andReturn($scalar);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from')->andReturn($where);

        $model = new TestModel2();

        $create = [
            'id' => 2,
            'id2' => 4,
            'required' => 25,
            'unique' => 'fail', ];
        $this->assertFalse($model->create($create));

        // verify error
        $this->assertCount(1, $errorStack->errors('TestModel2.create'));
    }

    public function testCreateInvalid()
    {
        $errorStack = self::$app['errors'];
        $errorStack->clear();
        $newModel = new TestModel2();
        $this->assertFalse($newModel->create(['id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true]));
        $this->assertCount(1, $errorStack->errors('TestModel2.create'));
    }

    public function testCreateMissingRequired()
    {
        $errorStack = self::$app[ 'errors' ];
        $errorStack->clear();
        $newModel = new TestModel2();
        $this->assertFalse($newModel->create([ 'id' => 10, 'id2' => 1 ]));
        $this->assertCount(1, $errorStack->errors('TestModel2.create'));
    }

    public function testCreateFail()
    {
        // insert qquery mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('insert->into->execute')->andThrow(new Exception());

        // logger mock
        self::$app['logger'] = Mockery::mock();
        self::$app['logger']->shouldReceive('error');

        $newModel = new TestModel();
        $this->assertFalse($newModel->create(['relation' => '', 'answer' => 42, 'extra' => true]));
    }

    /////////////////////////////
    // SET
    /////////////////////////////

    public function testSet()
    {
        $model = new TestModel(10);

        $this->assertTrue($model->set([]));

        // update query mock
        $stmt = Mockery::mock('PDOStatement');
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')->andReturn($stmt);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['id' => 10]])->andReturn($execute);
        $values = Mockery::mock();
        $values->shouldReceive('values')->withArgs([[
            'answer' => 42, ]])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update')->withArgs(['TestModels'])->andReturn($values);

        $this->assertTrue($model->set('answer', 42));
    }

    public function testSetMultiple()
    {
        $model = new TestModel(11);

        // update query mock
        $stmt = Mockery::mock('PDOStatement');
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')->andReturn($stmt);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['id' => 11]])->andReturn($execute);
        $values = Mockery::mock();
        $values->shouldReceive('values')->withArgs([[
            'answer' => 'hello',
            'relation' => null, ]])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update')->withArgs(['TestModels'])->andReturn($values);

        $this->assertTrue($model->set([
            'answer' => 'hello',
            'relation' => '',
            'nonexistent_property' => 'whatever', ]));
    }

    public function testSetJson()
    {
        $stmt = Mockery::mock('PDOStatement');

        // update mock
        $where = Mockery::mock();
        $where->shouldReceive('where->execute')->andReturn($stmt);
        $values = Mockery::mock();
        $values->shouldReceive('values')->withArgs([['json' => '{"test":true,"test2":[1,2,3]}']])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update')->andReturn($values);

        // select mock
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['json' => '{"test":true,"test2":[1,2,3]}']);

        $json = ['test' => true, 'test2' => [1, 2, 3]];

        $model = new TestModel2(13);
        $model->set('json', $json);
        $this->assertEquals($json, $model->json);
    }

    public function testSetImmutableProperties()
    {
        $stmt = Mockery::mock('PDOStatement');

        // update mock
        $where = Mockery::mock();
        $where->shouldReceive('where->execute')->andReturn($stmt);
        $values = Mockery::mock();
        $values->shouldReceive('values')->withArgs([[]])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update')->andReturn($values);

        // select mock
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([]);

        $model = new TestModel(10);

        $this->assertTrue($model->set('id', 432));
        $this->assertEquals(10, $model->id);

        $this->assertTrue($model->set('mutable_create_only', 'blah'));
        $this->assertEquals(null, $model->mutable_create_only);
    }

    public function testSetFailWithNoId()
    {
        $model = new TestModel();
        $this->assertFalse($model->set([ 'answer' => 42 ]));
    }

    public function testSetNoPermission()
    {
        $errorStack = self::$app['errors'];
        $errorStack->clear();
        $model = new TestModelNoPermission(5);
        $this->assertFalse($model->set('answer', 42));
        $this->assertCount(1, $errorStack->errors('TestModelNoPermission.set'));
    }

    public function testSetHookFail()
    {
        $model = new TestModelHookFail(5);
        $this->assertFalse($model->set('answer', 42));
    }

    public function testSetUnique()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->scalar')->andReturn(0);
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn(true);

        $model = new TestModel2(12);
        $this->assertTrue($model->set('unique', 'works'));
    }

    public function testSetUniqueSkip()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn(['unique' => 'works']);
        self::$app['db']->shouldReceive('update->values->where->execute')->andReturn(true);

        $model = new TestModel2(12);
        $this->assertTrue($model->set('unique', 'works'));
    }

    public function testSetInvalid()
    {
        $errorStack = self::$app['errors'];
        $errorStack->clear();
        $model = new TestModel2(15);

        $this->assertFalse($model->set('validate2', 'invalid'));
        $this->assertCount(1, $errorStack->errors('TestModel2.set'));
    }

    public function testSetFail()
    {
        $model = new TestModel(10);

        // update query mock
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('update->values->where->execute')->andThrow(new Exception());
        self::$app['logger'] = Mockery::mock();
        self::$app['logger']->shouldReceive('error');

        $this->assertFalse($model->set('answer', 42));
    }

    /////////////////////////////
    // DELETE
    /////////////////////////////

    public function testDelete()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('delete->where->execute')->andReturn(true);

        $model = new TestModel2(1);
        $this->assertTrue($model->delete());
    }

    public function testDeleteWithNoId()
    {
        $model = new TestModel();
        $this->assertFalse($model->delete());
    }

    public function testDeleteWithHook()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('delete->where->execute')->andReturn(true);

        $model = new TestModel(100);

        $this->assertTrue($model->delete());
        $this->assertTrue($model->preDelete);
        $this->assertTrue($model->postDelete);
    }

    public function testDeleteNoPermission()
    {
        $errorStack = self::$app[ 'errors' ];
        $model = new TestModelNoPermission(5);
        $this->assertFalse($model->delete());
        $this->assertCount(1, $errorStack->errors('TestModelNoPermission.delete'));
    }

    public function testDeleteHookFail()
    {
        $model = new TestModelHookFail(5);
        $this->assertFalse($model->delete());
    }

    public function testDeleteFail()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('delete->where->execute')->andThrow(new Exception());
        self::$app['logger'] = Mockery::mock();
        self::$app['logger']->shouldReceive('error');

        $model = new TestModel2(1);
        $this->assertFalse($model->delete());
    }

    /////////////////////////////
    // CACHE
    /////////////////////////////

    public function testSetDefaultCache()
    {
        $cache = Mockery::mock('Stash\\Pool');

        TestModel::setDefaultCache($cache);
        for ($i = 0; $i < 5; $i++) {
            $model = new TestModel();
            $this->assertEquals($cache, $model->getCache());
        }

        TestModel::clearDefaultCache();
    }

    public function testSetCache()
    {
        $cache = Mockery::mock('Stash\\Pool');

        $model = new TestModel();
        $this->assertEquals($model, $model->setCache($cache));

        $this->assertEquals($cache, $model->getCache());
    }

    public function testCacheKey()
    {
        $model = new TestModel(5);
        $this->assertEquals('models/testmodel/5', $model->cacheKey());

        $model = new TestModel2(5);
        $this->assertEquals('models/testmodel2/5', $model->cacheKey());
    }
    public function testCacheHit()
    {
        $cache = new Stash\Pool();

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([
            'answer' => 42, ]);

        $model = new TestModel(100);
        $model->setCache($cache);

        // load from the db first
        $model->load(true);

        $this->assertEquals($model, $model->load());

        $this->assertEquals(42, $model->get('answer'));
    }

    public function testCacheMiss()
    {
        $cache = new Stash\Pool();

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([
            'answer' => 42, ]);

        $model = new TestModel(101);
        $model->setCache($cache);

        $this->assertEquals($model, $model->load());

        // value should now be cached
        $item = $cache->getItem($model->cacheKey());
        $value = $item->get();
        $this->assertFalse($item->isMiss());
        $expected = [
            'answer' => 42, ];
        $this->assertEquals($expected, $value);
    }

    public function testCache()
    {
        $cache = new Stash\Pool();

        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andReturn([
            'answer' => 42, ]);

        $model = new TestModel(102);
        $model->setCache($cache);

        // cache
        $this->assertEquals($model, $model->load()->cache());
        $item = $cache->getItem($model->cacheKey());
        $value = $item->get();
        $this->assertFalse($item->isMiss());

        // clear the cache
        $this->assertEquals($model, $model->clearCache());
        $item = $cache->getItem($model->cacheKey());
        $value = $item->get();
        $this->assertTrue($item->isMiss());
    }

    /////////////////////////////
    // DATABASE
    /////////////////////////////

    public function testLoadFromDb()
    {
        $model = new TestModel2();
        $this->assertEquals($model, $model->load());

        // select query mock
        $one = Mockery::mock();
        $one->shouldReceive('one')->andReturn([]);
        $where = Mockery::mock();
        $where->shouldReceive('where')->withArgs([['id' => 12]])->andReturn($one);
        $from = Mockery::mock();
        $from->shouldReceive('from')->withArgs(['TestModels'])->andReturn($where);
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select')->andReturn($from)->once();

        $model = new TestModel(12);
        $this->assertEquals($model, $model->load(true));
    }

    public function testLoadFromDbFail()
    {
        self::$app['db'] = Mockery::mock();
        self::$app['db']->shouldReceive('select->from->where->one')->andThrow(new Exception())->once();

        $model = new TestModel(12);
        $this->assertEquals($model, $model->load(true));
    }
}

class TestModel extends Model
{
    static $properties = [
        'relation' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'TestModel2',
            'null' => true,
        ],
        'answer' => [
            'type' => Model::TYPE_STRING,
        ],
    ];
    public $preDelete;
    public $postDelete;

    protected static function propertiesHook()
    {
        $properties = parent::propertiesHook();

        $properties[ 'test_hook' ] = [
            'type' => Model::TYPE_STRING,
            'null' => true, ];

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
        if (!isset($exclude[ 'toArray' ])) {
            $result[ 'toArray' ] = true;
        }
    }
}

function validate()
{
    return false;
};
class TestModel2 extends Model
{
    static $properties = [
        'id' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'id2' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'default' => [
            'default' => 'some default value',
        ],
        'validate' => [
            'validate' => 'email',
            'null' => true,
        ],
        'validate2' => [
            'validate' => 'validate',
            'hidden' => true,
            'null' => true,
        ],
        'unique' => [
            'unique' => true,
        ],
        'required' => [
            'type' => Model::TYPE_NUMBER,
            'required' => true,
        ],
        'hidden' => [
            'type' => Model::TYPE_BOOLEAN,
            'default' => false,
            'hidden' => true,
        ],
        'person' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'Person',
            'default' => 20,
            'hidden' => true,
        ],
        'json' => [
            'type' => Model::TYPE_JSON,
            'default' => '{"tax":"%","discounts":false,"shipping":false}',
            'hidden' => true,
        ],
        'mutable_create_only' => [
            'mutable' => Model::MUTABLE_CREATE_ONLY,
            'hidden' => true,
        ],
    ];

    public static $autoTimestamps;

    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (isset($include[ 'toArrayHook' ])) {
            $result[ 'toArrayHook' ] = true;
        }
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
            'type' => Model::TYPE_STRING,
        ],
        'name' => [
            'type' => Model::TYPE_STRING,
            'default' => 'Jared',
        ],
        'address' => [
            'type' => Model::TYPE_STRING,
        ],
    ];

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}
