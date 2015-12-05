<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pimple\Container;
use Stash\Pool;

require_once 'tests/test_models.php';

class CacheablelTest extends PHPUnit_Framework_TestCase
{
    public static $app;

    public static function setUpBeforeClass()
    {
        // set up DI
        self::$app = new Container();

        CacheableModel::inject(self::$app);
    }

    public function testGetCachePool()
    {
        $cache = Mockery::mock('Stash\Pool');

        CacheableModel::setCachePool($cache);
        for ($i = 0; $i < 5; ++$i) {
            $model = new CacheableModel();
            $this->assertEquals($cache, $model->getCachePool());
        }
    }

    public function testNoPool()
    {
        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');
        $driver->shouldReceive('loadModel')
               ->andReturn(['answer' => 42]);
        CacheableModel::setDriver($driver);

        CacheableModel::setCachePool(null);
        $model = new CacheableModel(5);
        $this->assertNull($model->getCachePool());
        $this->assertNull($model->getCacheItem());
        $this->assertEquals($model, $model->refresh());
        $this->assertEquals($model, $model->cache());

        $model = new CacheableModel();
        $this->assertEquals($model, $model->refresh());
    }

    public function testGetCacheTTL()
    {
        $model = new CacheableModel();
        $this->assertEquals(10, $model->getCacheTTL());
    }

    public function testGetCacheKey()
    {
        $model = new CacheableModel(5);
        $this->assertEquals('models/cacheablemodel/5', $model->getCacheKey());
    }

    public function testGetCacheItem()
    {
        $cache = new Pool();
        CacheableModel::setCachePool($cache);

        $model = new CacheableModel(5);
        $item = $model->getCacheItem();
        $this->assertInstanceOf('Stash\Item', $item);
        $this->assertEquals('models/cacheablemodel/5', $item->getKey());

        $model = new CacheableModel(6);
        $item = $model->getCacheItem();
        $this->assertInstanceOf('Stash\Item', $item);
        $this->assertEquals('models/cacheablemodel/6', $item->getKey());
    }

    public function testCacheHit()
    {
        $cache = new Pool();

        $model = new CacheableModel(100);
        CacheableModel::setCachePool($cache);

        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn(['answer' => 42])
               ->once();

        CacheableModel::setDriver($driver);

        // load from the db first
        $this->assertEquals($model, $model->refresh());
        // load without skipping cache
        $this->assertEquals($model, $model->refresh());

        // this should be a hit from the cache
        $this->assertEquals(42, $model->answer);
    }

    public function testCacheMiss()
    {
        $cache = new Pool();

        $model = new CacheableModel(101);
        CacheableModel::setCachePool($cache);

        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn(['answer' => 42]);

        CacheableModel::setDriver($driver);

        $this->assertEquals($model, $model->refresh());

        // value should now be cached
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertFalse($item->isMiss());
        $expected = ['answer' => 42];
        $this->assertEquals($expected, $value);
    }

    public function testCache()
    {
        $model = new CacheableModel(102);
        $this->assertEquals($model, $model->cache());

        $cache = new Pool();
        CacheableModel::setCachePool($cache);

        $driver = Mockery::mock('Infuse\Model\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn(['answer' => 42]);

        CacheableModel::setDriver($driver);

        // cache
        $this->assertEquals($model, $model->refresh()->cache());
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertFalse($item->isMiss());

        // clear the cache
        $this->assertEquals($model, $model->clearCache());
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertTrue($item->isMiss());
    }
}
