<?php
namespace frame\core;

use \frame\core\Task;
class ServerWrapper
{
    private $server;
    private $task;

    public function __construct($server)
    {
        $this->server = $server;
        if (isset($server->setting['task_worker_num']) && $server->setting['task_worker_num'] > 0)
        	$this->task = true;
        else
        	$this->task = false;
    }

    public function addTask(Task $task)
    {
    	if ($this->task)
        	$this->server->task(serialize($task));
    }

    public function __call($name, $arguments)
    {
        call_user_func_array(array($this->server, $name), $arguments);
    }
}