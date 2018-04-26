<?php

return array(
    'bind_addr' => '0.0.0.0',
    'port' => 82,
    'debug_ip' => ['192.168.30.1', '127.0.0.1'],//限制日志输出的服务器
    'swoole' => [
<<<<<<< HEAD
        'daemonize'=>1, //守护进程化
=======
        //'daemonize'=>1, //守护进程化
>>>>>>> 1bb46809181da09fe8896a70930b83e8eb365cfe
        'pid_file' => '/var/run/swoole.pid',
        'log_file' => '/data/log/swoole.log',
        'worker_num' => 1,
        'backlog' => 128,       //listen backlog
        'max_request' => 1000,     // 一个worker进程在处理完超过此数值的任务后将自动退出，进程退出后会释放所有内存和资源。
        'dispatch_mode' => 2,
        'task_max_request ' => 1000, //同工作进程的 max_request
        'http_parse_post' => true,//自动将Content-Type为x-www-form-urlencoded的请求包体解析到POST数组
        'document_root' =>  __DIR__."/../public",//!! 注意这里需要修改项目的静态资源路径 sw 对静态文件支持不友好
        'enable_static_handler' => true,
        //'user'=>'www' //worker 进程用户，仅在使用root用户启动时有效
        'task_worker_num' => 2, //任务进程数量 执行耗时操作数量应该大于 worker
        //'heartbeat_idle_time' => 10,
        'heartbeat_check_interval' => 600,
    ]
);