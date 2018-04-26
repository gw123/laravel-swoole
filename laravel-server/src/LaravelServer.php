<?php
namespace LaravelServer;

use Illuminate\Http\Request;
use swoole_http_request;
use swoole_process;
use swoole_websocket_frame;
use swoole_websocket_server;


class LaravelServer extends SwooleServer
{
    /**
     * laravel http kernel.
     *
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $kernel;

    public function __construct($config, \Illuminate\Contracts\Http\Kernel $kernel)
    {
        parent::__construct($config);
        $this->kernel = $kernel;
    }

    public function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame)
    {
        //SwooleLog::debug('onMessage');
        $ws_request = new SwooleWebsocketRequest($server, $frame);
        $baseRequest = $ws_request->getNormalRequest();

        Request::enableHttpMethodParameterOverride();
        $request = Request::createFromBase($baseRequest);
        $laravel_response = $this->kernel->handle($request);

        ob_start();
        $laravel_response->send();
        $content = ob_get_contents();
        ob_end_clean();

        $response = ['data' => $content];
        $server->push($frame->fd, json_encode($response));
    }

    public function onRequest(swoole_http_request $request, \swoole_http_response $response)
    {
        if (isset($request->header['sec-websocket-version'])) {
            return;
        }

        $sw_request = new SwooleHttpRequest($this->swooleServer, $request);
        $baseRequest = $sw_request->getNormalRequest();

        Request::enableHttpMethodParameterOverride();
        $request = Request::createFromBase($baseRequest);
        $laravel_response = $this->kernel->handle($request);

        ob_start();
        $laravel_response->send();
        $content = ob_get_contents();
        ob_end_clean();

        $response->end($content);
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

}