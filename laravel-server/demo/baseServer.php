<?php

class ServerTest extends \LaravelServer\SwooleServer{
    public function onRequest(swoole_http_request $request, \swoole_http_response $sw_response)
    {
    }

    public function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
    }

    public function onTask(swoole_websocket_server $server, $task_id, $src_worker_id, $data)
    {
    }
}

require __DIR__ . '/../../../../autoload.php';
$serverConfig = require_once __DIR__ . '/../config/server.php';
$server = new ServerTest($serverConfig);
$server->start();

