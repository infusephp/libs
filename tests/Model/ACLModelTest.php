<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\ErrorStack;
use infuse\Locale;
use infuse\Model\ACLModel;
use Pimple\Container;

require_once 'tests/test_models.php';

class ACLModelTest extends PHPUnit_Framework_TestCase
{
    public static $app;
    public static $requester;

    public static function setUpBeforeClass()
    {
        // set up DI
        self::$app = new Container();
        self::$app['locale'] = function () {
            return new Locale();
        };
        self::$app['errors'] = function ($app) {
            return new ErrorStack($app);
        };

        ACLModel::inject(self::$app);

        self::$requester = new Person(1);
        ACLModel::setRequester(self::$requester);
    }

    public function testRequester()
    {
        $requester = new Person(2);
        ACLModel::setRequester($requester);
        $this->assertEquals($requester, ACLModel::getRequester());
    }

    public function testCan()
    {
        $acl = new AclObject();

        $this->assertFalse($acl->can('whatever', new TestModel()));
        $this->assertTrue($acl->can('do nothing', new TestModel(5)));
        $this->assertFalse($acl->can('do nothing', new TestModel()));
    }

    public function testCache()
    {
        $acl = new AclObject();

        for ($i = 0; $i < 10; ++$i) {
            $this->assertFalse($acl->can('whatever', new TestModel()));
        }
    }

    public function testGrantAll()
    {
        $acl = new AclObject();

        $acl->grantAllPermissions();

        $this->assertTrue($acl->can('whatever', new TestModel()));
    }

    public function testEnforcePermissions()
    {
        $acl = new AclObject();

        $this->assertEquals($acl, $acl->grantAllPermissions());
        $this->assertEquals($acl, $acl->enforcePermissions());

        $this->assertFalse($acl->can('whatever', new TestModel()));
    }

    public function testCreateNoPermission()
    {
        $errorStack = self::$app['errors']->clear();

        $newModel = new TestModelNoPermission();
        $this->assertFalse($newModel->create([]));
        $this->assertCount(1, $errorStack->errors());
    }

    public function testSetNoPermission()
    {
        $errorStack = self::$app['errors']->clear();

        $model = new TestModelNoPermission(5);
        $this->assertFalse($model->set('answer', 42));
        $this->assertCount(1, $errorStack->errors());
    }

    public function testDeleteNoPermission()
    {
        $errorStack = self::$app['errors']->clear();
        $model = new TestModelNoPermission(5);
        $this->assertFalse($model->delete());
        $this->assertCount(1, $errorStack->errors());
    }
}
