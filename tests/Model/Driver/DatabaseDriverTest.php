<?php

use infuse\Model;
use infuse\Model\Driver\DatabaseDriver;
use Pimple\Container;

class DatabaseDriverTest extends PHPUnit_Framework_TestCase
{
    public function testDatabase()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $driver = new DatabaseDriver($db);
        $this->assertEquals($db, $driver->getDatabase());
    }

    public function testSerializeValue()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $driver = new DatabaseDriver($db);

        $property = ['type' => Model::TYPE_STRING];
        $this->assertEquals('string', $driver->serializeValue($property, 'string'));

        $property = ['type' => Model::TYPE_JSON];
        $obj = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($property, $obj));
    }

    public function testUnserializeValue()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $driver = new DatabaseDriver($db);

        $property = ['null' => true];
        $this->assertEquals(null, $driver->unserializeValue($property, ''));

        $property = ['type' => Model::TYPE_STRING, 'null' => false];
        $this->assertEquals('string', $driver->unserializeValue($property, 'string'));

        $property = ['type' => Model::TYPE_BOOLEAN, 'null' => false];
        $this->assertTrue($driver->unserializeValue($property, true));
        $this->assertTrue($driver->unserializeValue($property, '1'));
        $this->assertFalse($driver->unserializeValue($property, false));

        $property = ['type' => Model::TYPE_NUMBER, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));

        $property = ['type' => Model::TYPE_DATE, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), $driver->unserializeValue($property, 'Aug-20-2015'));

        $property = ['type' => Model::TYPE_JSON, 'null' => false];
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, '{"test":true}'));
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, ['test' => true]));
    }

    public function testCreateModel()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');

        // insert query mock
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')
                ->andReturn(true);
        $into = Mockery::mock();
        $into->shouldReceive('into')
             ->withArgs(['Users'])
             ->andReturn($execute);
        $db->shouldReceive('insert')
           ->withArgs([['answer' => 42]])
           ->andReturn($into)
           ->once();

        $driver = new DatabaseDriver($db);
        User::setDriver($driver);

        $model = new User();
        $this->assertTrue($driver->createModel($model, ['answer' => 42]));
    }

    public function testCreateModelFail()
    {
        // insert query mock
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('insert->into->execute')
           ->andThrow(new PDOException());

        // logger mock
        $app = new Container();
        $app['logger'] = Mockery::mock();
        $app['logger']->shouldReceive('error')
                      ->once();

        $driver = new DatabaseDriver($db, $app);

        $model = new User();
        $this->assertFalse($driver->createModel($model, ['answer' => 42]));
    }

    public function testLoadModel()
    {
        // select query mock
        $one = Mockery::mock();
        $one->shouldReceive('one')
            ->andReturn(['name' => 'John']);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([['id' => 12]])
              ->andReturn($one);
        $from = Mockery::mock();
        $from->shouldReceive('from')
             ->withArgs(['Users'])
             ->andReturn($where);
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('select')
           ->andReturn($from)
           ->once();

        $driver = new DatabaseDriver($db);

        $model = new User(12);
        $this->assertEquals(['name' => 'John'], $driver->loadModel($model));
    }

    public function testLoadModelFail()
    {
        // select query mock
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('select->from->where->one')
           ->andThrow(new PDOException());

        // logger mock
        $app = new Container();
        $app['logger'] = Mockery::mock();
        $app['logger']->shouldReceive('error')
                      ->once();

        $driver = new DatabaseDriver($db, $app);

        $model = new User(12);
        $this->assertEquals([], $driver->loadModel($model));
    }

    public function testUpdateModel()
    {
        // update query mock
        $stmt = Mockery::mock('PDOStatement');
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')->andReturn($stmt);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([['id' => 11]])
              ->andReturn($execute);
        $values = Mockery::mock();
        $values->shouldReceive('values')
               ->withArgs([['name' => 'John']])
               ->andReturn($where);
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('update')
           ->withArgs(['Users'])
           ->andReturn($values);

        $driver = new DatabaseDriver($db);
        User::setDriver($driver);

        $model = new User(11);

        $this->assertTrue($driver->updateModel($model, []));

        $parameters = ['name' => 'John'];
        $this->assertTrue($driver->updateModel($model, $parameters));
    }

    public function testUpdateModelFail()
    {
        // update query mock
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('update->values->where->execute')
           ->andThrow(new PDOException());

        // logger mock
        $app = new Container();
        $app['logger'] = Mockery::mock();
        $app['logger']->shouldReceive('error')
                      ->once();

        $driver = new DatabaseDriver($db, $app);
        User::setDriver($driver);

        $model = new User(10);

        $parameters = ['name' => 'John'];
        $this->assertFalse($driver->updateModel($model, $parameters));
    }

    public function testDeleteModel()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('delete->where->execute')
           ->andReturn(true);

        $driver = new DatabaseDriver($db);
        User::setDriver($driver);

        $model = new User(10);
        $this->assertTrue($driver->deleteModel($model));
    }

    public function testDeleteModelFail()
    {
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('delete->where->execute')
           ->andThrow(new PDOException());

        // logger mock
        $app = new Container();
        $app['logger'] = Mockery::mock();
        $app['logger']->shouldReceive('error')
                      ->once();

        $driver = new DatabaseDriver($db, $app);
        User::setDriver($driver);

        $model = new User(10);
        $this->assertFalse($driver->deleteModel($model));
    }

    public function totalRecords()
    {
        // select query mock
        $scalar = Mockery::mock();
        $scalar->shouldReceive('scalar')
               ->andReturn(1);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([[]])
              ->andReturn($scalar);
        $from = Mockery::mock();
        $from->shouldReceive('from')
             ->withArgs(['TestModel2s'])
             ->andReturn($where);
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('select')
           ->withArgs(['count(*)'])
           ->andReturn($from);

        $driver = new DatabaseDriver($db, $app);
        User::setDriver($driver);

        $this->assertEquals(1, $driver->totalRecords('User', []));
    }

    public function totalRecordsFail()
    {
        // select query mock
        $db = Mockery::mock('JAQB\\QueryBuilder');
        $db->shouldReceive('select->from->where->scalar')
           ->andThrow(new Exception());

        // logger mock
        $app = new Container();
        $app['logger'] = Mockery::mock();
        $app['logger']->shouldReceive('error')
                      ->once();

        $driver = new DatabaseDriver($db, $app);
        User::setDriver($driver);

        $this->assertEquals(0, $driver->totalRecords('User', []));
    }
}

class User extends Model
{
    public static $properties = [
        'id' => [
            'type' => Model::TYPE_STRING,
        ],
        'name' => [
            'type' => Model::TYPE_STRING,
        ],
        'email' => [
            'type' => Model::TYPE_STRING,
        ],
    ];

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}
