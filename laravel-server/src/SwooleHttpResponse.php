<?php
namespace LaravelServer;

use swoole_http_request;
use swoole_websocket_frame;
use swoole_websocket_server;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class  SwooleHttpResponse
{
    /***
     * @var  \swoole_websocket_server
     */
    protected $swooleServer;

    /***
     * @var  \swoole_http_request
     */
    protected $frame;

    public $header;
    public $get;
    public $post;
    public $session_id;

    public function __construct(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
        $this->swooleServer = $server;
        $this->frame = $frame;

    }

    public function parseRequest(swoole_websocket_frame $frame)
    {
        $request = json_decode($frame->data);
        if (!$request) {
            throw new \Exception('websocket 协议解析失败');
        }

        return $request;
    }

    /***
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function createRequest(array  $sw_request)
    {
        $get = $sw_request['get'] ? $sw_request['get'] : [];
        $post = $sw_request['post'] ? $sw_request['post'] : [];
        $cookie = $sw_request['cookie'] ? $sw_request['cookie'] : [];
        $header = $sw_request['header'] ? $sw_request['header'] : [];
        $files = [];
        $header['content-type'] = 'application/x-www-form-urlencoded';
        foreach ($header as $key => $val) {
            $serverConverts[strtoupper($key)] = $val;
        }
        $request = new SymfonyRequest($get, $post, array(), $cookie, $files, $serverConverts);

        return $request;
    }

}