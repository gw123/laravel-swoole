<?php
namespace LaravelServer;

use swoole_websocket_frame;
use swoole_websocket_server;

class  SwooleWebsocketResponse
{
    /***
     * @var  \swoole_websocket_server
     */
    protected $swooleServer;

    /***
     * @var  \swoole_http_request
     */
    protected $frame;
    protected $fd;
    public $header = [];
    public $session_id = '';

    public $body = '';
    public $code = 200;

    public function __construct(swoole_websocket_server $server, $fd)
    {
        $this->swooleServer = $server;
        $this->fd = $fd;
    }

    public function createWebsocketFrame($data, $code = 200, $header = [])
    {
        $frame['body'] = $data;
        $frame['header'] = $header;
        $frame['code'] = $code;
        return $frame;
    }

    public function sendFrmae($data, $code = 200, $header = [])
    {
        $frmae = $this->createWebsocketFrame($data, $code, $header);
        $this->send($frmae);
    }

    public function send($data)
    {
        $this->swooleServer->push($this->fd, $data);
    }

}