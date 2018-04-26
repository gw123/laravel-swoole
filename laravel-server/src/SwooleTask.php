<?php
namespace LaravelServer;
abstract class SwooleTask
{
    protected $taskName = 'taskname';

    protected $config = [];

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    abstract public function handel();


}