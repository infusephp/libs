<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\Acl;

require_once 'test_models.php';

class AclTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $driver = Mockery::mock('infuse\\Model\\Driver\\DriverInterface');
        TestModel::setDriver($driver);
    }

    public function testRequester()
    {
        $requester = new Person(2);
        Acl::setRequester($requester);
        $this->assertEquals($requester, Acl::getRequester());
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
}
