![image](https://github.com/gw123/laravel-swoole/blob/master/smallApi.png?raw=true)
[![Latest Version](https://img.shields.io/badge/unstable-v1.0-yellow.svg?maxAge=2592000)]()
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)]()
# 目录说明
![image](https://github.com/gw123/laravel-swoole/blob/master/%E6%A1%86%E6%9E%B6%E8%AF%B4%E6%98%8E%E5%9B%BE.png?raw=true)

# 
使用swoole加速laravel ,原有的项目代码无需变动! 原有的项目代码无需变动! 原有的项目代码无需变动!!!

- 使用swoole加速laravel ,经过测试速度可以提高5倍以上
- 实现websocket和http会话同步机制 ，在websocket断线重连后可以方便的恢复原来的会话

# 安装 
- 直接 git clone git@github.com:gw123/laravel-swoole.git
- 修改根目录下的脚本文件 smallApi 修改为自己的安装位置
```
prefix=/data/wwwroot/laravelTest  #项目安装目录
php_bin=/data/install/php/bin/php #php执行文件位置
app_entry=${prefix}/api/api.php   #入口脚本位置
app_pid_file=/var/run/swoole.pid  #pid文件
```
# 运行
- ./smallApi start 启动服务
- ./smallApi stop 关闭服务
- ./smallApi reload 热重启服务，类似nginx reload
- ./smallApi restart 请求服务
- ./smallApi check 检测服务是否正常，这个命令可以配合一个 定时脚本用来检测服务是否正常，服务异常可以自动重启

### 可以将脚本放到 /etc/init.d目录下面 配合 chkconfig  命令实现开机自动运行 chkconfig --add smallApi

#配置文件
config/server.php
    'bind_addr' => '0.0.0.0',  //绑定地址
    'port' => 82,              //绑定端口
    'debug_ip' => ['192.168.30.1', '127.0.0.1'],//限制日志输出的服务器
    'swoole' => []             //服务器配置参数