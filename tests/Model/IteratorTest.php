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
use infuse\Model\Query;

class IteratorTest extends PHPUnit_Framework_TestCase
{
    public static $driver;
    public static $query;
    public static $iterator;
    public static $start = 10;
    public static $limit = 50;
    public static $totalRecords = 123;
    public static $noResults;

    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');

        $driver->shouldReceive('queryModels')
               ->andReturnUsing(function ($query) {
                    if (IteratorTest::$noResults) {
                        return [];
                    }

                    $range = range($query->getStart(), $query->getStart() + $query->getLimit() - 1);

                    foreach ($range as &$i) {
                        $i = ['id' => $i];
                    }

                    return $range;
               });

        $driver->shouldReceive('totalRecords')
               ->andReturnUsing(function () {
                    return IteratorTest::$totalRecords;
                });

        self::$driver = $driver;
        IteratorTestModel::setDriver(self::$driver);

        self::$query = new Query('IteratorTestModel');
        self::$query->start(self::$start)
                    ->limit(self::$limit);
        self::$iterator = new Iterator(self::$query);
    }

    protected function tearDown()
    {
        self::$totalRecords = 123;
        self::$noResults = false;
        self::$iterator->rewind();
    }

    public function testGetQuery()
    {
        $this->assertEquals(self::$query, self::$iterator->getQuery());

        // the default sorting should be by ID in ascending order
        $this->assertEquals([['id', 'asc']], self::$iterator->getQuery()->getSort());
    }

    public function testSetMax()
    {
        $query = new Query('IteratorTestModel');
        $iterator = new Iterator($query);

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
        $n = 0;
        foreach (self::$iterator as $k => $model) {
            $this->assertEquals($i, $k);
            $this->assertInstanceOf('IteratorTestModel', $model);
            $this->assertEquals($i, $model->id());
            ++$i;
            ++$n;
        }

        // last model ID that should have been produced
        $this->assertEquals(IteratorTestModel::totalRecords(), $i);

        // total # of records we should have iterated over
        $this->assertEquals(IteratorTestModel::totalRecords() - self::$start, $n);
    }

    public function testForeachChangingCount()
    {
        self::$totalRecords = 200;

        $i = self::$start;
        $n = 0;
        foreach (self::$iterator as $k => $model) {
            $this->assertEquals($i, $k);
            $this->assertInstanceOf('IteratorTestModel', $model);
            $this->assertEquals($i, $model->id());
            ++$i;
            ++$n;

            // simulate increasing the # of records midway
            if ($i == 51) {
                self::$totalRecords = 300;
                $this->assertCount(300, self::$iterator);
            // simulate decreasing the # of records midway
            } elseif ($i == 101) {
                self::$totalRecords = 26;
                $this->assertCount(26, self::$iterator);

                // The assumption is that the deleted records were
                // before the pointer. In order to not skip over
                // potential records the pointer is shifted
                // backwards. After the shift there should be N
                // records left to iterate over.
                $this->assertEquals(0, self::$iterator->key());
                $i = 1;
            }
        }

        // last model ID that should have been produced
        $this->assertEquals(26, $i);

        // total # of records we should have iterated over
        $this->assertEquals(116, $n);
    }

    public function testForeachFromZero()
    {
        $start = 0;
        $limit = 101;
        $query = new Query('IteratorTestModel');
        $query->limit(101);
        $iterator = new Iterator($query);

        $i = $start;
        foreach ($iterator as $k => $model) {
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

    public function testWithMax()
    {
        $query = new Query('IteratorTestModel');
        $iterator = new Iterator($query);
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

    public function testQueryModelsMismatchCount()
    {
        // simulate the queryModels() method acting up
        self::$noResults = true;

        foreach (self::$iterator as $k => $model) {
            // should always return a null model
            $this->assertNull($model);
        }
    }
}
