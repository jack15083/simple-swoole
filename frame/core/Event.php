<?php
namespace frame\core;
use frame\base\Request;
use frame\base\Response;

/**
 * 协议基类，实现一些公用的方法
 * @package
 */
class Event
{
    public $server;
    public $protocol;
    public $route;

    function __construct()
    {
        $this->init();
    }

    public function init()
    {

    }

    /**
     * output log
     * @param $msg
     * @param string $type
     */
    public function log($msg)
    {
        $log = "[" . date("Y-m-d G:i:s") . " " . floor(microtime() * 1000) . "]" . $msg;
        echo $log, NL;
    }

    public function setServer($server)
    {
        $this->server = $server;
    }

    public function onStart($serv, $workerId)
    {
        //initStart
        if (!empty($this->protocol)) {
            $this->protocol->onStart($serv, $workerId);
        }
    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {

    }

    public function onClose($server, $fd, $fromId)
    {

    }

    public function onTask($serv, $taskId, $fromId, $data)
    {
        $task = unserialize($data);
        call_user_func(array($task, 'onTask'));
        $serv->finish(serialize($task));
    }

    public function onFinish($serv, $taskId, $data)
    {
        $task = unserialize($data);
        call_user_func(array($task, 'onFinish'));
    }

    public function onTimer($serv, $interval)
    {

    }

    public function onRequest(Request $request, Response $response)
    {
        $route = $this->protocol->onRoute($request);
        $class = $route->class;
        $fun   = $route->action;
        $data  = $route->data;

        if ((!class_exists($class) || !method_exists(($class), ($fun)))) {
            if ($response->servType=='http'){
                $response->status(404);
            };

            $response->send('not found');
            return;
        }

        if (is_array($request->data))
            if ($response->servType == 'http') {
                if (isset($request->data['get'])) $request->data['get'] = array_merge((array)$request->data['get'], $data);
                else $request->data['get'] = $data;
            } else {
                $request->data = array_merge($request->data, $data);
            }
        else
            $request->data =$data;

        $obj = new $class($request, $response);
        $obj->run($fun);
    }

    public function setRegister($type, $instance)
    {
        $this->$type = $instance;
    }
}