<?php
namespace LaravelServer;

use swoole_http_request;
use swoole_websocket_frame;
use swoole_websocket_server;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class  SwooleWebsocketRequest
{
    /***
     * @var  \swoole_websocket_server
     */
    protected $swooleServer;

    /***
     * @var  \swoole_http_request
     */
    protected $frame;
    protected $request;

    public $header;
    public $get;
    public $post;
    public $session_id;

    public function __construct(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
        $this->swooleServer = $server;
        $this->frame = $frame;
        $this->parseRequest($frame);
    }

    public function parseRequest(swoole_websocket_frame $frame)
    {
        $request = json_decode($frame->data ,true);
        if (!$request) {
            throw new \Exception('websocket 协议解析失败');
        }
        $this->request = $request;
    }

    /***
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getNormalRequest(  )
    {
        $sw_request = $this->request;
        $get =  isset($sw_request['get']) ? $sw_request['get'] : [];
        $post = isset($sw_request['post']) ? $sw_request['post'] : [];
        $cookie = isset($sw_request['cookie']) ? $sw_request['cookie'] : [];
        $header = isset($sw_request['header']) ? $sw_request['header'] : [];
        $files = [];

        foreach ($header as $key => $val) {
            $sh[strtoupper($key)] = $val;
        }
        $sh["REQUEST_METHOD"] = isset($sh["REQUEST_METHOD"])? $sh["REQUEST_METHOD"]:"GET";
        $sh['PATH_INFO'] =  isset($sh['PATH_INFO'])? $sh['PATH_INFO'] :'/';
        $sh['CONTENT_TYPE'] =  isset($sh['CONTENT_TYPE'])? $sh['CONTENT_TYPE'] :'application/x-www-form-urlencoded';
        $sh['HTTP_HOST'] =  isset($sh['HTTP_HOST'])? $sh['HTTP_HOST'] : '';

        $sh['SERVER_PORT'] =  isset($sh['SERVER_PORT'])? $sh['SERVER_PORT'] :'';
        $sh['REMOTE_PORT'] =  isset($sh['REMOTE_PORT'])? $sh['REMOTE_PORT'] :'';
        $sh['REMOTE_ADDR'] =  isset($sh['REMOTE_ADDR'])? $sh['REMOTE_ADDR'] :'';
        $sh['SERVER_PROTOCOL'] =  isset($sh['SERVER_PROTOCOL'])? $sh['SERVER_PROTOCOL'] : 'HTTP/1.0';
        $sh['REQUEST_URI'] = $sh['PATH_INFO'];

        $request = new SymfonyRequest($get, $post, array(), $cookie, $files, $sh);

        return $request;
    }

}