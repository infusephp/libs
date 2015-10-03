<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\ErrorStack;
use Infuse\Locale;
use Pimple\Container;

class ErrorStackTest extends PHPUnit_Framework_TestCase
{
    public static $app;
    public static $stack;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();
        self::$app['locale'] = new Locale();
        self::$stack = new ErrorStack(self::$app);
    }

    public function testConstruct()
    {
        $stack = new ErrorStack(self::$app);
    }

    public function testPush()
    {
        $error1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong', ];

        $this->assertEquals(self::$stack, self::$stack->push($error1));

        $error2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username', ], ];

        $this->assertEquals(self::$stack, self::$stack->push($error2));

        $this->assertEquals(self::$stack, self::$stack->push('some_error'));
    }

    /**
     * @depends testPush
     */
    public function testErrors()
    {
        $expected1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong',
            'context' => '',
            'params' => [], ];

        $expected2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username', ], ];

        $expected3 = [
            'error' => 'some_error',
            'message' => 'some_error',
            'context' => '',
            'params' => [], ];

        $errors = self::$stack->errors();
        $this->assertEquals(3, count($errors));
        $this->assertEquals([$expected1, $expected2, $expected3], $errors);

        $errors = self::$stack->errors('user.create');
        $this->assertEquals(1, count($errors));
        $this->assertEquals([$expected2], $errors);
    }

    /**
     * @depends testPush
     */
    public function testMessages()
    {
        $expected = [
            'Something is wrong',
            'Username is invalid',
            'some_error', ];

        $messages = self::$stack->messages();
        $this->assertEquals(3, count($messages));
        $this->assertEquals($expected, $messages);

        $expected = ['Username is invalid'];

        $messages = self::$stack->messages('user.create');
        $this->assertEquals(1, count($messages));
        $this->assertEquals($expected, $messages);
    }

    /**
     * @depends testPush
     */
    public function testFind()
    {
        $expected = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username', ], ];

        $this->assertEquals($expected, self::$stack->find('username'));
        $this->assertEquals($expected, self::$stack->find('username', 'field'));

        $this->assertFalse(self::$stack->find('non-existent'));
    }

    /**
     * @depends testPush
     */
    public function testHas()
    {
        $this->assertTrue(self::$stack->has('username'));
        $this->assertTrue(self::$stack->has('username', 'field'));

        $this->assertFalse(self::$stack->has('non-existent'));
        $this->assertFalse(self::$stack->has('username', 'something'));
    }

    /**
     * @depends testErrors
     * @depends testMessages
     */
    public function testSetCurrentContext()
    {
        $this->assertEquals(self::$stack, self::$stack->setCurrentContext('test.context'));

        $this->assertEquals(self::$stack, self::$stack->push(['error' => 'test_error']));

        $expected = [
            'error' => 'test_error',
            'context' => 'test.context',
            'params' => [],
            'message' => 'test_error', ];
        $this->assertEquals([$expected], self::$stack->errors('test.context'));
    }

    /**
     * @depends testErrors
     * @depends testMessages
     */
    public function testClearCurrentContext()
    {
        $this->assertEquals(self::$stack, self::$stack->clearCurrentContext());

        $this->assertEquals(self::$stack, self::$stack->push(['error' => 'test_error']));

        $expected = [
            'error' => 'test_error',
            'context' => '',
            'params' => [],
            'message' => 'test_error', ];
        $errors = self::$stack->errors('');
        $this->assertTrue(in_array($expected, $errors));
    }

    /**
     * @depends testErrors
     */
    public function testClear()
    {
        $this->assertEquals(self::$stack, self::$stack->clear());
        $this->assertCount(0, self::$stack->errors());
    }

    public function testIterator()
    {
        self::$stack->clear();
        for ($i = 1; $i <= 5; ++$i) {
            self::$stack->push("$i");
        }

        $result = [];
        foreach (self::$stack as $k => $v) {
            $result[$k] = $v['error'];
        }

        $this->assertEquals(['1', '2', '3', '4', '5'], $result);

        self::$stack->next();
        $this->assertNull(self::$stack->current());
    }

    public function testCount()
    {
        self::$stack->clear();
        self::$stack->push('Test');
        $this->assertCount(1, self::$stack);
    }

    public function testArrayAccess()
    {
        self::$stack->clear();

        self::$stack[0] = 'test';
        $this->assertTrue(isset(self::$stack[0]));
        $this->assertFalse(isset(self::$stack[6]));

        $this->assertEquals('test', self::$stack[0]['error']);
        unset(self::$stack[0]);
    }

    public function testArrayGetFail()
    {
        $this->setExpectedException('OutOfBoundsException');

        echo self::$stack['invalid'];
    }

    public function testArraySetFail()
    {
        $this->setExpectedException('Exception');

        self::$stack['invalid'] = 'test';
    }
}
