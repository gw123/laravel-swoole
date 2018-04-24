<?php
namespace LaravelServer;

class  SwooleLog
{
    const ERR_SYS = 401;
    const ERR_ARGUMENT = 403;
    const ERR_CLOSED = 402;
    const ERR_NOYFOUND = 404;

    const ERR_TASK_MISSTASKNAME = 501;
    const ERR_TASK_NOYFOUND = 504;
    const ERR_TASK_CHECK_FAILED = 502;

    public static function debug($msg, $file = '', $line = 0)
    {
        $data['type'] = 'debug';
        $data['place'] = "FILE :" . $file . " : " . $line;
        $data['msg'] = $msg;
        self::logger($data, 'debug');
    }

    public static function info($msg, $file = '', $line = 0)
    {
        $data['type'] = 'info';
        $data['place'] = "FILE :" . $file . " : " . $line;
        $data['msg'] = $msg;
        self::logger($data, 'sys_info');
    }

    public static function waring($msg, $file = '', $line = 0)
    {
        $data['type'] = 'warn';
        $data['place'] = "FILE :" . $file . " : " . $line;
        $data['msg'] = $msg;
        self::logger($data, 'sys_waring');
    }

    public static function error($msg, $file = '', $line = 0)
    {
        $data['type'] = 'error';
        $data['place'] = "FILE :" . $file . " : " . $line;
        $data['msg'] = $msg;
        self::logger($data, 'sys_error');
    }

    public static function fatal($msg, $file = '', $line = 0)
    {
        $data['type'] = 'fatal';
        $data['place'] = "FILE :" . $file . " : " . $line;
        $data['msg'] = $msg;
        self::logger($data, 'sys_fatal');
    }

    /***
     * 输出调试日志
     * @param $content array|string  日志内容可
     * @param string $group 日志分类
     * @param int $fd 指定客户端
     */
    public static function logger($content, $group = 'info', $fd = 0)
    {
        $task['target'] = 'logger';
        $task['type'] = $content['type'];
        $task['data'] = $content['msg'];
        $task['place'] = $content['place'];
        $task['group'] = $group;
        $task['fd'] = $fd;
        echo $content['msg'];
    }
}