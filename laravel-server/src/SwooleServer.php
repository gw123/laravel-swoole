<?php
namespace LaravelServer;

use Illuminate\Http\Request;
use swoole_http_request;
use swoole_http_response;
use swoole_process;
use swoole_websocket_frame;
use swoole_websocket_server;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

abstract class SwooleServer
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


    protected  $webSocketFds;

    public static $channel;

    /**
     * laravel http kernel.
     *
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $kernel;

    public function __construct($config)
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
        $this->boot();

        //这里不够友好
        SwooleLog::$server  = $this;

    }

    public function boot()
    {
        //创建websocket服务
        $swooleServer = new swoole_websocket_server($this->bindAddr, $this->bindPort);
        //设置swoole配置
        $swooleServer->set($this->config);

        $swooleServer->on('Start', array($this, 'onStart'));
        $swooleServer->on('WorkerStart', array($this, 'onWorkerStart'));
        $swooleServer->on('Open', array($this, 'onOpen'));
        //$swooleServer->on('Connect', array($this, 'onConnect'));
        //$swooleServer->on('Receive', array($this, 'onReceive'));
        $swooleServer->on('Message', array($this, 'onMessage'));
        $swooleServer->on('Request', array($this, 'onRequest'));
        $swooleServer->on('Close', array($this, 'onClose'));
        $swooleServer->on('WorkerStop', array($this, 'onWorkStop'));

        $swooleServer->on('Task', array($this, 'onTask'));
        $swooleServer->on('Finish', array($this, 'onFinish'));
        $swooleServer->on('Shutdown', array($this, 'onShutdown'));
        $this->swooleServer = $swooleServer;

        //记录 websocket 连接

//        self::$webSocketFds = new  \Swoole\Table(65536);
//        self::$webSocketFds->column('session_id', \Swoole\Table::TYPE_STRING, 64);
//        self::$webSocketFds->column('is_debug', \Swoole\Table::TYPE_INT, 1);
//        self::$webSocketFds->column('ip', \Swoole\Table::TYPE_STRING, 15);
//        self::$webSocketFds->create();

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

    /***
     * 创建channel  连接
     */
    public function createChannel()
    {
        $channel = new \Swoole\Channel(60000);
        self::$channel = $channel;
    }

    public function onStart(swoole_websocket_server $server)
    {
        //创建用户管理进程
        SwooleLog::debug('主线程创建');
    }

    /***
     * 处理http 协议
     */
    abstract public function onRequest(swoole_http_request $request, \swoole_http_response $sw_response);


    public function onHandShake(swoole_http_request $request, swoole_http_response $response)
    {
        SwooleLog::debug('onHandShake');
    }

    /***
     *  webscoket 协议握手成功后触发 ,在指定hangShake握手协议后不会触发onOpen
     */
    public function onOpen(swoole_websocket_server $server, swoole_http_request $request)
    {
        SwooleLog::debug('onOpen');
        $fd = $request->fd;
        $data['ip'] = $request->server['remote_addr'];
    }

    abstract public function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame);

    /***
     * websocket 断开连接
     */
    public function onClose(swoole_websocket_server $server, int $fd, int $reactorId)
    {
        SwooleLog::info('client close');
    }


    public function bordercast($data){
        if(!is_array($data)){
            return false;
        }

        foreach ($this->swooleServer->connections as $connection){
            $fdinfo =  $this->swooleServer->connection_info($connection);
            if(isset($fdinfo['websocket_status'])&&$fdinfo['websocket_status']==3){
                $this->swooleServer->push($connection , json_encode($data));
            }

        }
    }

    abstract public function onTask(swoole_websocket_server $server, $task_id, $src_worker_id, $data);

    public function onFinish($server, $task_id, $data)
    {

    }

    /***
     * 启动work进程
     */
    public function onWorkerStart(swoole_websocket_server $server, int $process_id)
    {
        if (!$this->swooleServer->taskworker) {
            $str = "Worker 进程。。。";
            //Reload
        } else {
            $str = "Task 进程。。。";
        }
        SwooleLog::debug($str);
    }

    public function onWorkStop($server, $worker_id)
    {
        $str = "进程结束 销毁内存";
        SwooleLog::debug($str);
    }

    public function start()
    {
        $this->swooleServer->start();
    }

    public function onShutdown(swoole_websocket_server $server)
    {
        SwooleLog::info('服务关闭');
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

}