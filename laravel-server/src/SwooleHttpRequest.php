<?php
namespace LaravelServer;

use swoole_http_request;
use swoole_websocket_frame;
use swoole_websocket_server;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class  SwooleHttpRequest
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

    /***
     * @var  \Symfony\Component\HttpFoundation\Request
     */
    protected $normalRequest;

    public function __construct(swoole_websocket_server $server, swoole_http_request $frame)
    {
        $this->swooleServer = $server;
        $this->frame = $frame;
        $this->parseRequest();
    }

    public function parseRequest()
    {
        $this->normalRequest =  $this->createRequest($this->frame);
    }

    /***
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getNormalRequest()
    {
        return $this->normalRequest;
    }

    /***
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function createRequest(swoole_http_request $sw_request)
    {
        $get = $sw_request->get ? $sw_request->get : [];
        $post = $sw_request->post ? $sw_request->post : [];
        $cookie = $sw_request->cookie ? $sw_request->cookie : [];
        $files = $sw_request->files ? $sw_request->files : [];
        $servers = $sw_request->server ? $sw_request->server : [];
        $headers = $sw_request->server ? $sw_request->header : [];
        if (isset($sw_request->header['content-type']) &&
            strpos($sw_request->header['content-type'], 'application/json') !== false
        ) {
            $post = json_decode($sw_request->rawContent() ,true);
        }

        $serverConverts = [];
        foreach ($headers as $key => $val) {
            $serverConverts['HTTP_' . strtoupper($key)] = $val;
        }
        foreach ($servers as $key => $val) {
            $serverConverts[strtoupper($key)] = $val;
        }
        //dd($serverConverts);
        $request = new SymfonyRequest($get, $post, array(), $cookie, $files, $serverConverts,$sw_request->rawContent());

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

}