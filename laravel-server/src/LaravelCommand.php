<?php
namespace LaravelServer;

use Illuminate\Console\Command;

class ServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server {action=start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SwooleServer 控制命令';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        $script_path = __DIR__."/../demo/laravelSwoole.php";
        $pid_file = config('server.swoole.pid_file');
        switch ($action) {
            case 'start':
                exec("php ".$script_path);
                break;
            case 'stop':
                exec("kill -TERM `cat {$pid_file}`");
                break;
            case 'reload':
                exec("kill -USR2 `cat {$pid_file}`");
                break;
            case 'restart':
                exec("kill -TERM `cat {$pid_file}`");
                exec("php ".$script_path);
                break;
            default:
                echo "参数必须是 start ,stop ,reload,restart\n";
                break;
        }
        //
        echo "laravelSwoole server ".$action." .\n";

    }
}
