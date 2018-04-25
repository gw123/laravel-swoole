<?php
namespace LaravelServer;

use Illuminate\Http\Request;
use swoole_http_request;
use swoole_process;
use swoole_websocket_server;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class LaravelServer
{
    /***
     * @var  \swoole_websocket_server
     */
    protected $swooleServer;

    protected $bindAddr;
    protected $bindPort;
    protected $debugIps;

    protected $config;

    protected $processes;

    public static $web_socket_fds;
    public static $channel;

    /**
     * laravel http kernel.
     *
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $kernel;

    public function __construct($config, \Illuminate\Contracts\Http\Kernel $kernel)
    {
        if (isset($config['bind_addr'])) {
            $this->bindAddr = $config['bind_addr'];
        } else {
            $this->bindAddr = '0.0.0.0';
        }
        if (isset($config['bind_port'])) {
            $this->bindPort = $config['bind_port'];
        } else {
            $this->bindPort = '82';
        }

        $this->config = isset($config['swoole']) ? $config['swoole'] : [];
        $this->kernel = $kernel;
        $this->boot();
    }

    public function boot()
    {
        //创建websocket服务
        $swooleServer = new swoole_websocket_server($this->bindAddr, $this->bindPort);
        //设置swoole配置
        $swooleServer->set($this->config);

        $swooleServer->on('Start', array($this, 'onStart'));
        $swooleServer->on('WorkerStart', array($this, 'onWorkerStart'));
        $swooleServer->on('Receive', array($this, 'onReceive'));
        $swooleServer->on('Close', array($this, 'onClose'));
        $swooleServer->on('WorkerStop', array($this, 'onWorkStop'));
        $swooleServer->on('Open', array($this, 'onOpen'));
        $swooleServer->on('Message', array($this, 'onMessage'));
        $swooleServer->on('Request', array($this, 'onRequest'));
        $swooleServer->on('Task', array($this, 'onTask'));
        $swooleServer->on('Finish', array($this, 'onFinish'));
        //$swooleServer->start();

        $this->swooleServer = $swooleServer;

        //记录 websocket 连接
        self::$web_socket_fds = new  \Swoole\Table(65536);
        self::$web_socket_fds->column('session_id', \Swoole\Table::TYPE_STRING, 48);
        self::$web_socket_fds->column('is_debug', \Swoole\Table::TYPE_INT, 1);
        self::$web_socket_fds->column('ip', \Swoole\Table::TYPE_STRING, 15);
        self::$web_socket_fds->create();
    }

    public function onOpen(swoole_websocket_server $server, swoole_http_request $request)
    {
        $fd = $request->fd;
        $data['ip'] = $request->server['remote_addr'];
        //self::$web_socket_fds->set($fd, $data);
    }

    public function onMessage(swoole_websocket_server $server, $frame)
    {
        //self::dispatchWs($frame, $server);
    }

    public function onReceive(swoole_websocket_server $server, $frame)
    {

    }

    public function onRequest(swoole_http_request $request, \swoole_http_response $sw_response)
    {
        if (isset($request->header['sec-websocket-version'])) {
            return;
        }

        Request::enableHttpMethodParameterOverride();
        $baseRequest = $this->createRequest($request);

        $request = Request::createFromBase($baseRequest);
        $response = $this->kernel->handle($request);

        ob_start();
        $response->send();
        $content = ob_get_contents();
        ob_end_clean();
        $sw_response->end($content);
    }

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
        $request = new SymfonyRequest($get, $post, array(), $cookie, $files, $serverConverts,$sw_request->rawContent());

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    public function onTask(swoole_websocket_server $server, $task_id, $src_worker_id, $data)
    {
        if (!isset($data['class'])) {
            SwooleLog::error('必须指定任务类名' . json_encode($data), __FILE__, __LINE__);
            $server->finish(['code' => SwooleLog::ERR_TASK_MISSTASKNAME, 'msg' => '必须指定任务类名']);
            return;
        }
        if (!class_exists($data['class'])) {
            SwooleLog::error('指定任务类不存在:' . $data['class'], __FILE__, __LINE__);
            $server->finish(['code' => SwooleLog::ERR_NOYFOUND, 'msg' => '指定任务类不存在']);
            return;
        }
        if (!isset($data['data'])) {
            $data['data'] = [];
        }

        $task = new $data['class']($server, $task_id, $src_worker_id, $data['data']);
        if (!$task->beforeRun()) {
            $server->finish(['code' => SwooleLog::ERR_TASK_CHECK_FAILED, 'msg' => $task->getErrors()]);
            return;
        }
        $task_result = $task->run();
//        $wrap_result['task_id'] = $task_id;
//        $wrap_result['src_worker_id'] = $src_worker_id;
//        $wrap_result['class'] = $data['class'];
//        $wrap_result['input'] = $data['data'];
//        $wrap_result['result'] = $task_result;

        $server->finish(serialize($task));
    }

    public function onFinish($server, $task_id, $data)
    {

    }

    public function onStart()
    {
        $str = "Swoole server is started at {$this->bindAddr}:{$this->bindPort}\n";
        SwooleLog::info($str);
    }

    public function onWorkerStart()
    {
        if (!$this->swooleServer->taskworker) {
            $str = "Worker 进程 初始化框架。。。\n";
        } else {
            $str = "Task 初始化框架。。。\n";
        }
        echo $str;
        //初始化进程生命周期的内容
    }

    public function onWorkStop($server, $worker_id)
    {
        $str = "进程结束 销毁内存";
        echo $str;
    }

    public function onClose()
    {
        //SwooleLog::info('close');
    }

    public function createProcess()
    {
        /***
         * 管理进程，在这里实现远程重启操作
         */
        $channel = $this->createChannel();
        $server = $this->swooleServer;
        $process = new \swoole_process(function (swoole_process $process) use ($server, $channel) {
            swoole_timer_tick(1000, function () use ($server, $channel) {
                while ($data = $channel->pop()) {
                    if ($data == 'reload') {
                        echo "Reload\n";
                        $this->swooleServer->reload();
                    }
                }
            });

            /***
             * 定时更新系统信息
             */
            swoole_timer_tick(2000, function () use ($server, $channel) {
                exec('top -n 1 -b -c', $out);
            });

        });
        $this->swooleServer->addProcess($process);
    }

    public function createChannel()
    {
        //创建channel  连接
        $channel = new \Swoole\Channel(60000);
        self::$channel = $channel;
    }

    public function start()
    {
        $this->swooleServer->start();
    }

    public function errorHandel()
    {
        /**
         * E_ALL =》运行错误
         */
        set_error_handler(function ($errno, $errmsg, $errfile, $errline) {
            $msg = "File : " . $errfile . " => " . $errline . "\n Msg: " . $errmsg . "\n";
            echo $msg;
        });
        /**处理语法错误*/
        register_shutdown_function(function () {
            $msg = error_get_last();
            echo "语法错误；\n";
            print_r($msg);
            echo "\n swoole 调试信息 : \n";
            $msg['message'] = str_replace("\n", " ", $msg['message']);
        });
    }

    public function output($msg)
    {
        echo $msg;
    }
}