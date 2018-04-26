![image](https://github.com/gw123/laravel-swoole/blob/master/smallApi.png?raw=true)
[![Latest Version](https://img.shields.io/badge/unstable-v1.0-yellow.svg?maxAge=2592000)]()
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)]()
# 目录说明
![image](https://github.com/gw123/laravel-swoole/blob/master/%E6%A1%86%E6%9E%B6%E8%AF%B4%E6%98%8E%E5%9B%BE.png?raw=true)

# 
使用swoole加速laravel ,原有的项目代码无需变动! 原有的项目代码无需变动! 原有的项目代码无需变动!!!

- 使用swoole加速laravel
- 实现websocket和http会话同步机制 ，在websocket断线重连后可以方便的恢复原来的会话

# 安装 
- 直接 omposer require gw123/laravel-swoole
- 修改根目录下的脚本文件 smallApi 修改为自己的安装位置
```
prefix=/data/wwwroot/laravelTest  #项目安装目录
php_bin=/data/install/php/bin/php #php执行文件位置
app_entry=${prefix}/api/api.php   #入口脚本位置
app_pid_file=/var/run/swoole.pid  #pid文件

```
# 运行方式1
- 修改app/Console/Kernel ,在$commands数组中添加 LaravelServer\ServerCommand::class.
- php artisan server 启动服务器
- php artisan stop 启动服务器
- php artisan restart 重启服务器

# 运行方式2
- chmod +x server
- ./smallApi start 启动服务
- ./smallApi stop 关闭服务
- ./smallApi reload 热重启服务，类似nginx reload
- ./smallApi restart 请求服务
- ./smallApi check 检测服务是否正常，这个命令可以配合一个 定时脚本用来检测服务是否正常，服务异常可以自动重启

### 可以将脚本放到 /etc/init.d目录下面 配合 chkconfig  命令实现开机自动运行 chkconfig --add smallApi

#配置文件
```
config/server.php
    'bind_addr' => '0.0.0.0',  //绑定地址
    'port' => 82,              //绑定端口
    'debug_ip' => ['192.168.30.1', '127.0.0.1'],//限制日志输出的服务器
    'swoole' => []             //服务器配置参数
```

# demo文件夹 示例代码说明(下面代码直接 php scriptname 方式运行)
-   baseServer 基础服务
-   laravelServer 基于laravel框架的swoole服务器
-   wsClient 模拟测试laraveServer服务器的websocket请求


#webscoket 请求协议详细参考 wsClient代码
```
    $frame = '{"header":{},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    $frame = '{"header":{"path_info":"/test"},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    $frame = '{"header":{"path_info":"/gogo"},"get":{"username":"gw123","password":123456},"post":{},"body":"value"}';
    
    header 同http协议中的 header ,唯一不同的是需要指定  path_info 等同get的请求路径.
    所以协议默认请求下是模拟get请求 提交方式为 application/www-url-decode
    get  可以指定get参数
    post 指定post参数 需要配置header  设置REQUEST_METHOD为 POST
    body 是原始请求体用来实现一些特殊的请求(完善中)
```

# 调试 
- 将resource/debug.html 放到web目录下面
- http://youhost/debug.html
- 输入服务端网址 点击连接
- 发送调试信息到浏览器: \LaravelServer\SwooleLog::debug('not foung'); 
