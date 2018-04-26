<?php
//基于larvel的服务器
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../../../../autoload.php';
//require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
<<<<<<< HEAD
$config_file =   app_path()."/config/server.php";
if(!is_file($config_file)){
    $config_file = __DIR__ . '/../config/server.php';
}
$serverConfig = require_once $config_file;

$server = new \LaravelServer\LaravelServer($serverConfig, $kernel);

=======

$serverConfig = require_once __DIR__ . '/../config/server.php';
$server = new \LaravelServer\LaravelServer($serverConfig, $kernel);
>>>>>>> 1bb46809181da09fe8896a70930b83e8eb365cfe
$server->start();

