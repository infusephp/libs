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
use Pimple\Container;

require_once 'tests/test_models.php';

class IteratorTest extends \PHPUnit_Framework_TestCase
{
    public static $app;
    public static $iterator;
    public static $start = 10;
    public static $limit = 50;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();
        self::$app['db'] = Mockery::mock('JAQB\\QueryBuilder');
        IteratorTestModel::inject(self::$app);

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
                $this->assertEquals($i, $current->id());
            } else {
                $this->assertEquals(null, $current);
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
            $this->assertEquals($i, $model->id());
            ++$i;
        }

        $this->assertEquals($i, IteratorTestModel::totalRecords());
    }

    public function testFindAll()
    {
        $iterator = IteratorTestModel::findAll();

        $i = 0;
        foreach ($iterator as $k => $model) {
            $this->assertEquals($i, $k);
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
            $this->assertEquals($i, $model->id());
            ++$i;
        }

        $this->assertEquals($i, IteratorTestModel::totalRecords());
    }

    public function testWithMax()
    {
        $iterator = new Iterator('IteratorTestModel');
        $iterator->setMax(5);

        $found = [];
        foreach ($iterator as $model) {
            $found[] = $model->id();
        }

        $expected = [0, 1, 2, 3, 4];

        $this->assertEquals($expected, $found);
    }
}
