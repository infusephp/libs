<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\Model\Iterator;

require_once 'tests/test_models.php';

class IteratorTest extends PHPUnit_Framework_TestCase
{
    public static $driver;
    public static $iterator;
    public static $start = 10;
    public static $limit = 50;

    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $driver->shouldReceive('queryModels')
               ->andReturnUsing(function ($model, $query) {
                    $range = range($query->getStart(), $query->getStart() + $query->getLimit() - 1);

                    foreach ($range as &$i) {
                        $i = ['id' => $i];
                    }

                    return $range;
               });

        $driver->shouldReceive('totalRecords')
               ->andReturn(123);

        IteratorTestModel::setDriver($driver);
        self::$driver = $driver;

        self::$iterator = new Iterator('IteratorTestModel', [
            'start' => self::$start,
            'limit' => self::$limit, ]);
    }

    public function testConstructSearch()
    {
        $iterator = new Iterator('IteratorTestModel', [
            'search' => 'test', ]);
    }

    public function testSetMax()
    {
        $iterator = new Iterator('IteratorTestModel');

        $this->assertEquals(-1, $iterator->getMax());
        $this->assertEquals($iterator, $iterator->setMax(100));
        $this->assertEquals(100, $iterator->getMax());
    }

    public function testKey()
    {
        $this->assertEquals(self::$start, self::$iterator->key());
    }

    public function testValid()
    {
        $this->assertTrue(self::$iterator->valid());
    }

    public function testNext()
    {
        for ($i = self::$start; $i < self::$limit + 1; ++$i) {
            self::$iterator->next();
        }

        $this->assertEquals(self::$limit + 1, self::$iterator->key());
    }

    public function testRewind()
    {
        self::$iterator->rewind();

        $this->assertEquals(self::$start, self::$iterator->key());
    }

    public function testCurrent()
    {
        self::$iterator->rewind();

        $count = IteratorTestModel::totalRecords();
        for ($i = self::$start; $i < $count + 1; ++$i) {
            $current = self::$iterator->current();
            if ($i < $count) {
                $this->assertInstanceOf('IteratorTestModel', $current);
                $this->assertEquals($i, $current->id());
            } else {
                $this->assertNull($current);
            }

            self::$iterator->next();
        }
    }

    public function testNotValid()
    {
        self::$iterator->rewind();
        for ($i = self::$start; $i < IteratorTestModel::totalRecords() + 1; ++$i) {
            self::$iterator->next();
        }

        $this->assertFalse(self::$iterator->valid());
    }

    public function testForeach()
    {
        $i = self::$start;
        foreach (self::$iterator as $k => $model) {
            $this->assertEquals($i, $k);
            $this->assertInstanceOf('IteratorTestModel', $model);
            $this->assertEquals($i, $model->id());
            ++$i;
        }

        $this->assertEquals($i, IteratorTestModel::totalRecords());
    }

    public function testCount()
    {
        $this->assertCount(123, self::$iterator);
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset(self::$iterator[0]));
        $this->assertFalse(isset(self::$iterator[123]));
        $this->assertFalse(isset(self::$iterator['blah']));
    }

    public function testOffsetGet()
    {
        $this->assertEquals(0, self::$iterator[0]->id());
        $this->assertEquals(1, self::$iterator[1]->id());
    }

    public function testOffsetGetOOB()
    {
        $this->setExpectedException('OutOfBoundsException');

        $fail = self::$iterator[100000];
    }

    public function testOffsetSet()
    {
        $this->setExpectedException('Exception');

        self::$iterator[0] = null;
    }

    public function testOffsetUnset()
    {
        $this->setExpectedException('Exception');

        unset(self::$iterator[0]);
    }

    public function testFindAll()
    {
        $iterator = IteratorTestModel::findAll();

        $i = 0;
        foreach ($iterator as $k => $model) {
            $this->assertEquals($i, $k);
            $this->assertInstanceOf('IteratorTestModel', $model);
            $this->assertEquals($i, $model->id());
            ++$i;
        }

        $this->assertEquals($i, IteratorTestModel::totalRecords());
    }

    public function testFromZero()
    {
        $start = 0;
        $limit = 101;
        $iterator = new Iterator('IteratorTestModel', [
            'start' => $start,
            'limit' => $limit, ]);

        $i = $start;
        foreach ($iterator as $k => $model) {
            $this->assertEquals($i, $k);
            $this->assertInstanceOf('IteratorTestModel', $model);
            $this->assertEquals($i, $model->id());
            ++$i;
        }

        $this->assertEquals($i, IteratorTestModel::totalRecords());
    }

    public function testWithMax()
    {
        $iterator = new Iterator('IteratorTestModel');
        $iterator->setMax(5);

        // test Iterator
        $found = [];
        foreach ($iterator as $model) {
            $this->assertInstanceOf('IteratorTestModel', $model);
            $found[] = $model->id();
        }

        $expected = [0, 1, 2, 3, 4];

        $this->assertEquals($expected, $found);

        // test Countable
        $this->assertCount(5, $iterator);

        // test ArrayAccess
        $this->assertTrue(isset($iterator[0]));
    }
}
