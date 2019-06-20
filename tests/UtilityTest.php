<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Utility;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UtilityTest extends MockeryTestCase
{
    public function testArrayValue()
    {
        $a = [
            'test' => 2,
            'test2' => [
                '3' => [
                    '4' => [
                        'asldfj',
                    ],
                ],
                '5' => 1234,
            ],
        ];

        $this->assertEquals(Utility::arrayValue($a, 'test'), 2);
        $this->assertEquals(array_value($a, 'test'), 2);
        $this->assertEquals(Utility::arrayValue($a, 'test2.3.4'), ['asldfj']);
        $this->assertEquals(Utility::arrayValue($a, 'test2.5'), 1234);

        $this->assertNull(Utility::arrayValue($a, 'nonexistent'));
        $this->assertNull(Utility::arrayValue($a, 'some.nonexistent.property'));
    }

    public function testArraySet()
    {
        $a = [];

        Utility::arraySet($a, '1.2.3.4.5', 'test');
        $expected = ['1' => ['2' => ['3' => ['4' => ['5' => 'test']]]]];
        $this->assertEquals($expected, $a);

        array_set($a, 'test', 'ok?');
        $expected['test'] = 'ok?';
        $this->assertEquals($expected, $a);

        Utility::arraySet($a, '1.2.3', 'test');
        $expected['1']['2']['3'] = 'test';
        $this->assertEquals($expected, $a);
    }

    public function testArrayDot()
    {
        $a = ['1' => ['2' => ['3' => ['4' => ['5' => 'test']]]]];
        $expected = ['1.2.3.4.5' => 'test'];

        $this->assertEquals($expected, Utility::arrayDot($a));

        $a = [
            'fruit' => [
                'apples' => [
                    'sold' => 100,
                    'remaining' => 100,
                    'percent' => 0.5,
                ],
                'oranges' => [
                    'remaining' => 0,
                ],
            ],
            'test' => true,
        ];
        $expected = [
            'fruit.apples.sold' => 100,
            'fruit.apples.remaining' => 100,
            'fruit.apples.percent' => 0.5,
            'fruit.oranges.remaining' => 0,
            'test' => true,
        ];

        $this->assertEquals($expected, array_dot($a));
    }

    public function testGuid()
    {
        $guid1 = Utility::guid();
        $guid2 = Utility::guid();

        $this->assertEquals(36, strlen($guid1));
        $this->assertEquals(36, strlen($guid2));
        $this->assertTrue($guid1 != $guid2);

        $guid1 = Utility::guid(false);
        $guid2 = Utility::guid(false);

        $this->assertEquals(32, strlen($guid1));
        $this->assertEquals(32, strlen($guid2));
        $this->assertTrue($guid1 != $guid2);
    }

    public function testSeoify()
    {
        $this->assertEquals('some-test-string', Utility::seoify('some test string'));
        $this->assertEquals('meh', Utility::seoify('*)#%*^&--meh *#)$*#)*$'));
        $this->assertEquals('already-seoified-string', Utility::seoify('already-seoified-string'));
    }

    public function testParseMetricStr()
    {
        $this->assertEquals(1000000000000, Utility::parseMetricStr('1T'));
        $this->assertEquals(50000000000, Utility::parseMetricStr('50G'));
        $this->assertEquals(1400000, Utility::parseMetricStr('1.4M'));
        $this->assertEquals(2000, Utility::parseMetricStr('2K'));

        $this->assertEquals(1073741824, Utility::parseMetricStr('1GBytes', true));
    }

    public function testNumberAbbreviate()
    {
        $this->assertEquals('12.3K', Utility::numberAbbreviate(12345));
        $this->assertEquals('1M', Utility::numberAbbreviate(1000000, 2));

        $this->assertEquals('-1234', Utility::numberAbbreviate(-1234, 2));
        $this->assertEquals('123', Utility::numberAbbreviate(123, 3));
        $this->assertEquals('12.345K', Utility::numberAbbreviate(12345, 3));
        $this->assertEquals('12.345M', Utility::numberAbbreviate(12345000, 3));
        $this->assertEquals('1.23G', Utility::numberAbbreviate(1234567890, 2));
        $this->assertEquals('1.23T', Utility::numberAbbreviate(1234567890123, 2));
    }

    public function testSetCookieFixDomain()
    {
        $name = 'session.name';
        $value = rand();
        $expires = time() + 3600;
        $path = '/test/path';
        $domain = 'www.example.com';
        $secure = true;
        $httponly = true;

        $cookieStr = Utility::setCookieFixDomain(
            $name,
            $value,
            $expires,
            $path,
            $domain,
            $secure,
            $httponly,
            false);

        $expiresStr = gmdate('D, d-M-Y H:i:s T', $expires);

        $expected = "Set-Cookie: $name=$value; expires=$expiresStr; path=$path; domain=.example.com; secure; HttpOnly";

        $this->assertEquals($expected, $cookieStr);
    }

    public function testTimeAgo()
    {
        $this->assertEquals('10 seconds ago', Utility::timeAgo(strtotime('-10 seconds')));
        $this->assertEquals('5 minutes ago', Utility::timeAgo(strtotime('- 5 minutes')));
        $this->assertEquals('1 day ago', Utility::timeAgo(strtotime('- 1 day')));
        $this->assertEquals('1 week ago', Utility::timeAgo(strtotime('-1 week')));
        $this->assertEquals('1 month ago', Utility::timeAgo(strtotime('- 1 month')));
        $this->assertEquals('1 year ago', Utility::timeAgo(strtotime('-1 year')));

        $this->assertEquals('1 day, 1 minute, 40 seconds ago', Utility::timeAgo(strtotime('-86500 seconds'), true));
    }

    public function testTimeUntil()
    {
        // sometimes tests can run a little slow, hence the ranges
        $this->assertTrue(in_array(Utility::timeUntil(strtotime('+10 seconds')), ['9 seconds', '10 seconds']));
        $this->assertTrue(in_array(Utility::timeUntil(strtotime('+5 minutes')), ['4 minutes', '5 minutes']));
        $this->assertEquals('1 day', Utility::timeUntil(strtotime('+1 day') + 60));
        $this->assertEquals('1 week', Utility::timeUntil(strtotime('+1 week') + 60));
        $this->assertEquals('1 month', Utility::timeUntil(strtotime('+1 month') + 60));
        $this->assertEquals('1 year', Utility::timeUntil(strtotime('+ 1 year') + 60));

        $now = '1560999241';
        $timestamp = '1561085741'; // 86500 seconds later

        $this->assertEquals('1 day, 1 minute, 40 seconds', Utility::timeUntil($timestamp, true, $now));
    }

    public function testUnixToDb()
    {
        $t = mktime(23, 34, 20, 4, 18, 2012);
        $this->assertEquals('2012-04-18 23:34:20', Utility::unixToDb($t));
    }
}
