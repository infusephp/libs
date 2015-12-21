<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
date_default_timezone_set('America/Chicago');

exec('rm -rf '.dirname(__DIR__).'/temp');

require __DIR__.'/../vendor/autoload.php';
