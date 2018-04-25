<?php

$cli = new swoole_http_client('127.0.0.1', 82);

$cli->on('message', function ($_cli, $frame) {
    var_dump($frame);
    //file_put_contents('./local.log',json_encode($frame) ,FILE_APPEND);
});


$cli->upgrade('/', function ($cli) {
    //echo $cli->body;
    $frame = '{"header":{},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    $cli->push($frame);

    $frame = '{"header":{"path_info":"/test"},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    $cli->push($frame);

    $frame = '{"header":{"path_info":"/gogo"},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    $cli->push($frame);

});

