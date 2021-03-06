<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse;

class Utility
{
    /**
     * Looks up a key in an array. If the key follows dot-notation then a nested lookup will be performed.
     * i.e. users.sherlock.address.lat -> ['users']['sherlock']['address']['lat'].
     *
     * @param array  $a array to be searched
     * @param string $k key to search for
     *
     * @return mixed|null
     */
    public static function arrayValue(array $a = [], $k = '')
    {
        return array_value($a, $k);
    }

    /**
     * Sets an element in an array using dot notation (i.e. fruit.apples.qty sets ['fruit']['apples']['qty'].
     *
     * @param array  $a
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public static function arraySet(array &$a, $key, $value)
    {
        return array_set($a, $key, $value);
    }

    /**
     * Flattens a multi-dimensional array using dot notation
     * i.e. ['fruit' => ['apples' => ['qty' => 1]]] produces
     * [fruit.apples.qty => 1].
     *
     * @param array  $a      input array
     * @param string $prefix key prefix
     *
     * @return array output array
     */
    public static function arrayDot(array $a, $prefix = '')
    {
        return array_dot($a, $prefix);
    }

    /**
     * Generates a unique 32-digit GUID. i.e. 12345678-1234-5678-123456789012.
     *
     * @param bool $dashes whether or not to separate guid with dashes
     *
     * @return string
     */
    public static function guid($dashes = true)
    {
        if (function_exists('com_create_guid')) {
            return trim('{}', com_create_guid());
        } else {
            $charid = strtoupper(md5(uniqid(rand(), true)));

            $dash = $dashes ? '-' : '';

            $uuid = substr($charid, 0, 8).$dash.
                    substr($charid, 8, 4).$dash.
                    substr($charid, 12, 4).$dash.
                    substr($charid, 16, 4).$dash.
                    substr($charid, 20, 12);

            return $uuid;
        }
    }

    /**
     * Makes a string SEO compliant (numbers, digits, and dashes only).
     *
     * @param string $string
     * @param int    $maxLength   maximum length of result
     * @param array  $commonWords common words to remove
     *
     * @return string
     */
    public static function seoify($string, $maxLength = 150, $commonWords = [])
    {
        $string = strtolower(stripslashes($string));
        // kill HTML entities
        $string = preg_replace('/&.+?;/', '', $string);
        // kill anything that is not a letter, digit, space, dash
        $string = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
        // turn it to an array and strip common words by comparing against c.w. array
        $seo_slug_array = array_diff(explode(' ', $string), $commonWords);
        // turn the sanitized array into a string of max length
        $return = substr(implode('-', $seo_slug_array), 0, $maxLength);
        // allow only single runs of dashes
        $return = strtolower(preg_replace('/--+/u', '-', $return));
        // first character cannot be '-'
        if ($return[0] == '-') {
            $return = substr($return, 1);
        }
        // last character cannot be '-'
        if (substr($return, -1) == '-') {
            $return = substr($return, 0, -1);
        }

        return $return;
    }

    /**
     * Converts a human friendly metric string (i.e. 1G) into a number.
     *
     * @param string $str
     * @param string $use1024 when true, increment by 1,024 instead of 1,000
     *
     * @return number
     */
    public static function parseMetricStr($str, $use1024 = false)
    {
        // normalize
        $str = strtolower(trim($str));

        // strip off all letters and find suffix
        $i = strlen($str) - 1;
        while ($i >= 0 && !is_numeric($str[$i])) {
            --$i;
        }

        // last letter
        $last = $str[$i + 1];

        // get the number
        $val = substr($str, 0, $i + 1);

        // compute the value
        $thousand = ($use1024) ? 1024 : 1000;
        $hundred = ($use1024) ? 102.4 : 100;
        switch ($last) {
            case 't':
                $val *= $thousand;
            case 'g':
                $val *= $thousand;
            case 'm':
                $val *= $thousand;
            case 'k':
                $val *= 10;
            case 'h':
                $val *= $hundred;
        }

        return $val;
    }

    /**
     * Formats a number with a set number of decimals and a metric suffix
     * i.e. number_abbreviate( 12345, 2 ) -> 12.35K.
     *
     * @param int $number
     * @param int $decimals number of places after decimal
     *
     * @return string
     */
    public static function numberAbbreviate($number, $decimals = 1)
    {
        $abbrevs = [
            24 => 'Y',
            21 => 'Z',
            18 => 'E',
            15 => 'P',
            12 => 'T',
            9 => 'G',
            6 => 'M',
            3 => 'K',
            0 => '',
        ];

        foreach ($abbrevs as $exponent => $abbrev) {
            if ($number >= pow(10, $exponent)) {
                $remainder = $number % pow(10, $exponent).' ';
                $decimal = ($remainder > 0) ? round(round($remainder, $decimals) / pow(10, $exponent), $decimals) : 0;

                return (intval($number / pow(10, $exponent)) + $decimal).$abbrev;
            }
        }

        return $number;
    }

    /**
     * Sets the cookie with a properly formatted domain to fix older versions of IE dropping sessions.
     *
     * from php.net user comments
     *
     * @param string $name
     * @param string $value
     * @param int    $expires
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     * @param bool   $setHeader when true, sets the header, otherwise returns header string
     *
     * @return string
     */
    public static function setCookieFixDomain($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false, $setHeader = true)
    {
        if (!empty($domain)) {
            // Fix the domain to accept domains with and without 'www.'
          if (strtolower(substr($domain, 0, 4)) == 'www.') {
              $domain = substr($domain, 4);
          }
            $domain = '.'.$domain;

          // Remove port information
          $port = strpos($domain, ':');
            if ($port !== false) {
                $domain = substr($domain, 0, $port);
            }
        }

        $cookieStr = 'Set-Cookie: '.rawurlencode($name).'='.rawurlencode($value).
            (empty($expires) ? '' : '; expires='.gmdate('D, d-M-Y H:i:s', $expires).' GMT').
            (empty($path) ? '' : '; path='.$path).
            (empty($domain) ? '' : '; domain='.$domain).
            (!$secure ? '' : '; secure').
            (!$httponly ? '' : '; HttpOnly');

        if ($setHeader) {
            header($cookieStr, false);
        }

        return $cookieStr;
    }

    /**
     * Converts a UNIX timestamp to the database timestamp format.
     *
     * @param int $timestamp
     *
     * @return string
     */
    public static function unixToDb($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Generates a string for how long ago a timestamp happened.
     * i.e. '2 minutes ago' or 'just now'.
     *
     * @param int  $timestamp timestamp
     * @param bool $full      true: time ago has every granularity, false: time ago has biggest granularity only
     *
     * @return string computed time ago
     */
    public static function timeAgo($timestamp, $full = false)
    {
        $now = new \DateTime();
        $ago = new \DateTime();
        $ago->setTimestamp($timestamp);

        $string = self::timeDiff($now, $ago);

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string).' ago' : 'just now';
    }

    /**
     * Generates a string for how long until a timestamp will happen
     * i.e. '2 days' or 'now'.
     *
     * @param int  $timestamp timestamp
     * @param bool $full      true: time ago has every granularity, false: time ago has biggest granularity only
     * @param int|null $currentTime
     *
     * @return string computed time until
     */
    public static function timeUntil($timestamp, $full = false, $currentTime = null)
    {
        $now = new \DateTime();
        if ($currentTime) {
            $now->setTimestamp($currentTime);
        }

        $then = new \DateTime();
        $then->setTimestamp($timestamp);

        $string = self::timeDiff($then, $now);

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string) : 'now';
    }

    /**
     * Calculates the time difference between two DateTime objects
     * Borrowed from http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago.
     *
     * @param \DateTime $a "now"
     * @param \DateTime $b "then"
     *
     * @return array delta at time each granularity
     */
    private static function timeDiff(\DateTime $a, \DateTime $b)
    {
        $interval = $a->diff($b);

        $w = floor($interval->d / 7);
        $interval->d -= $w * 7;

        $diff = [
            'y' => $interval->y,
            'm' => $interval->m,
            'w' => $w,
            'd' => $interval->d,
            'h' => $interval->h,
            'i' => $interval->i,
            's' => $interval->s, ];

        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];
        foreach ($string as $k => &$v) {
            if ($diff[$k]) {
                $v = $diff[$k].' '.$v.($diff[$k] > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        return $string;
    }
}
