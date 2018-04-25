<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../../../autoload.php';
$serverConfig = require_once __DIR__ . '/../config/server.php';

$server = new \LaravelServer\SwooleServer($serverConfig);
$server->start();

