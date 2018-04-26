<?php
//基于larvel的服务器
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../../../../autoload.php';
//require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$config_file =   app_path()."/config/server.php";
if(!is_file($config_file)){
    $config_file = __DIR__ . '/../config/server.php';
}
$serverConfig = require_once $config_file;

$server = new \LaravelServer\LaravelServer($serverConfig, $kernel);

$server->start();

