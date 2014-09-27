<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Model;
use infuse\Model\Iterator;

class IteratorTest extends \PHPUnit_Framework_TestCase
{
    public static $iterator;
    public static $start = 10;
    public static $limit = 50;

    public static function setUpBeforeClass()
    {
        self::$iterator = new Iterator( 'IteratorTestModel', [
            'start' => self::$start,
            'limit' => self::$limit ] );
    }

    public function testConstructSearch()
    {
        $iterator = new Iterator( 'IteratorTestModel', [
            'search' => 'test' ] );
    }

    public function testKey()
    {
        $this->assertEquals( self::$start, self::$iterator->key() );
    }

    public function testValid()
    {
        $this->assertTrue( self::$iterator->valid() );
    }

    public function testNext()
    {
        for( $i = self::$start; $i < self::$limit + 1; $i++ )
            self::$iterator->next();

        $this->assertEquals( self::$limit + 1, self::$iterator->key() );
    }

    public function testRewind()
    {
        self::$iterator->rewind();

        $this->assertEquals( self::$start, self::$iterator->key() );
    }

    public function testCurrent()
    {
        self::$iterator->rewind();

        $count = IteratorTestModel::totalRecords();
        for ($i = self::$start; $i < $count + 1; $i++) {
            $current = self::$iterator->current();
            if( $i < $count )
                $this->assertEquals( $i, $current->id() );
            else
                $this->assertEquals( null, $current );

            self::$iterator->next();
        }
    }

    public function testNotValid()
    {
        self::$iterator->rewind();
        for( $i = self::$start; $i < IteratorTestModel::totalRecords() + 1; $i++ )
            self::$iterator->next();

        $this->assertFalse( self::$iterator->valid() );
    }

    public function testForeach()
    {
        $i = self::$start;
        foreach (self::$iterator as $k => $model) {
            $this->assertEquals( $i, $k );
            $this->assertEquals( $i, $model->id() );
            $i++;
        }

        $this->assertEquals( $i, IteratorTestModel::totalRecords() );
    }

    public function testFindAll()
    {
        $iterator = IteratorTestModel::findAll();

        $i = 0;
        foreach ($iterator as $k => $model) {
            $this->assertEquals( $i, $k );
            $this->assertEquals( $i, $model->id() );
            $i++;
        }

        $this->assertEquals( $i, IteratorTestModel::totalRecords() );
    }

    public function testFromZero()
    {
        $start = 0;
        $limit = 101;
        $iterator = new Iterator( 'IteratorTestModel', [
            'start' => $start,
            'limit' => $limit ] );

        $i = $start;
        foreach ($iterator as $k => $model) {
            $this->assertEquals( $i, $k );
            $this->assertEquals( $i, $model->id() );
            $i++;
        }

        $this->assertEquals( $i, IteratorTestModel::totalRecords()     );
    }
}

class IteratorTestModel extends Model
{
    static $properties = [
        'id' => [
            'type' => 'number' ],
        'id2' => [
            'type' => 'number' ],
        'name' => [
            'type' => 'string',
            'searchable' => true ]
    ];

    public static function idProperty()
    {
        return [ 'id', 'id2' ];
    }
    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public static function totalRecords(array $where = [])
    {
        return 123;
    }

    public static function find(array $params = [])
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
