<?php
namespace frame\core;

abstract class Runnable
{

    public $interval; //时间间隔
    public $logLevel; 
    public $num;
    // private $lock;
    private $workerId;
    private $subTaskId;
    private $className;

    /**
     * @param $interval interval in millis
     */
    public function __construct($taskConf, $interval = 30000, $logLevel = 'error', $num = 1)
    {
        $this->workerId = $taskConf['workerId'];
        $this->className = $taskConf['className'];
        $this->interval = $interval;
        $this->logLevel = $logLevel;
        $this->num = $num;
        $this->subTaskId = ($this->workerId) % $num;
    }

    public function start()
    {
        $this->run($this->subTaskId);
    }

    abstract public function run($subTaskId);

}