<?php

namespace frame\core;

use frame\base\Request;
use frame\base\Response;
use frame\log\Log;

class Server
{
    protected $sw; // swoole object
    protected $processName = 'swooleServ';
    protected $host = '0.0.0.0';
    protected $port = 8088;
    protected $listen;
    protected $mode = SWOOLE_PROCESS;
    protected $udpListener;
    protected $tcpListener;

    public $config = array(); //修改为public ---mark  20150620
    protected $setting = array();
    protected $runPath = '/tmp';
    protected $masterPidFile;
    protected $managerPidFile;
    protected $user;
    protected $enableHttp = false;
    //private $protocol;
    public $sockType;
    public $servType;


    private $preSysCmd = '%+-swoole%+-';
    private $requireFile = '';

    // public $serverClass;  //修改为public ---mark  20150620
    public $protocol;  //修改为public ---mark  20150620

    private $timers = [];

    function __construct()
    {
        // Initialization server startup parameters
        $this->setting = array_merge(array(
            'worker_num' => 8,                      // worker process num
            'backlog' => 128,                       // listen backlo
        ), $this->setting);

        $this->setHost();
        $this->init();
    }

    public function init()
    {

    }
    
    /**
     * set app index file
     * @param string $file
     * @throws \Exception
     */
    public function setRequire($file)
    {
        if (!file_exists($file)) {
            throw new \Exception("[error] require file :$file is not exists");
        }
        $this->requireFile = $file;
    }

    /**
     * set process name 
     * @param unknown $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }


    public function loadConfig($config = array())
    {
        if (is_string($config)) {   // $config is file path?
            if (!file_exists($config)) {
                throw new \Exception("[error] profiles [$config] can not be loaded");
            }
            // Load the configuration file into an array
            $config = parse_ini_file($config, true);
        }
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        return true;
    }

    protected function _initRunTime()
    {
        $mainSetting = $this->config['server'] ? $this->config['server'] : array();
        $runSetting = $this->config['setting'] ? $this->config['setting'] : array();
        //$this->processName = $mainSetting['server_name'] ? $mainSetting['server_name'] : 'swoole_server'; //todo
        $this->masterPidFile = $this->runPath . '/' . $this->processName . '.master.pid';
        $this->managerPidFile = $this->runPath . '/' . $this->processName . '.manager.pid';
        $this->setting = array_merge($this->setting, $runSetting);
        //     $this->serverClass = $mainSetting['server_name'] ? $mainSetting['server_name'] : 'swoole_server'; //todo

        // trans listener
        if ($mainSetting['listen']) {
            $this->transListener($mainSetting['listen']);
        }

        // set user
        if (isset($mainSetting['user'])) {
            $this->user = $mainSetting['user'];
        }

        if ($this->listen[0]) {
            $this->host = $this->listen[0]['host'] ? $this->listen[0]['host'] : $this->host;
            $this->port = $this->listen[0]['port'] ? $this->listen[0]['port'] : $this->port;
            unset($this->listen[0]);
        }
    }

    private function scanTimers($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $f) {
                //TODO end with php
                if (is_file($dir . $f)) {
                    $this->timers[] = $dir . $f;
                }
            }
        }
    }

    private function initServer()
    {
        //执行用户代码
        if (isset($this->config['server']['init'])) {
            $init = (include_once $this->config['server']['init']);
        }

        if (isset($this->config['timer']['timer_dir'])) {
            $this->scanTimers($this->config['timer']['timer_dir']);
        }

        switch ($this->servType) {
            case 'udp':
                $this->sockType = SWOOLE_SOCK_UDP;
                break;
            default:
                $this->sockType = SWOOLE_SOCK_TCP;


        };

        // Creating a swoole server resource object
        $swooleServerName = ($this->servType == 'http') ? '\swoole_http_server' : '\swoole_server';
        $this->sw = new $swooleServerName($this->host, $this->port, $this->mode, $this->sockType);
        $this->sw->servType = $this->servType;
        //提供给用户设定系统全局变量
        $this->sw->userParams = array();
        //一个临时的兼容
        $this->setting['worker_num'] = intval($this->setting['worker_num']);
        $this->setting['dispatch_mode'] = intval($this->setting['dispatch_mode']);
        $this->setting['daemonize'] = intval($this->setting['daemonize']);

        // $this->sw = new \swoole_http_server($this->host, $this->port, $this->mode, $this->sockType);
        // Setting the runtime parameters
        $this->sw->set($this->setting);

        // Set Event Server callback function
        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStart', array($this, 'onManagerStart'));
        $this->sw->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->sw->on('Connect', array($this, 'onConnect'));
        $this->sw->on('Receive', array($this, 'onReceive'));
        $this->sw->on('Close', array($this, 'onClose'));
        $this->sw->on('WorkerStop', array($this, 'onWorkerStop'));
        //$this->sw->on('timer', array($this, 'onTimer'));
        if ($this->servType == 'http') {
            $this->sw->on('Request', array($this, 'onRequest'));
        }
        if (isset($this->setting['task_worker_num'])) {
            $this->sw->on('Task', array($this, 'onTask'));
            $this->sw->on('Finish', array($this, 'onFinish'));
        }

        // add listener
        if (is_array($this->listen)) {
            foreach ($this->listen as $v) {
                if (!$v['host'] || !$v['port']) {
                    continue;
                }
                $this->sw->addlistener($v['host'], $v['port'], $this->sockType);
            }
        }
    }

    private function transListener($listen)
    {
        if (!is_array($listen)) {
            $tmpArr = explode(":", $listen);
            $host = isset($tmpArr[1]) ? $tmpArr[0] : $this->host;
            $port = isset($tmpArr[1]) ? $tmpArr[1] : $tmpArr[0];

            $this->listen[] = array(
                'host' => $host,
                'port' => $port,
            );
            return true;
        }
        foreach ($listen as $v) {
            $this->transListener($v);
        }
    }

    public function onMasterStart($server)
    {
        $this ->_setProcessName($this->processName . ': master process');
        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
        if ($this->user) {
            $this->changeUser($this->user);
        }
    }

    public function onManagerStart($server)
    {
        // rename manager process
        $this ->_setProcessName($this->processName . ': manager process');
        if ($this->user) {
            $this->changeUser($this->user);
        }
    }

    public function onWorkerStart($server, $workerId) {
        if ($this->user) {
            $this->changeUser($this->user);
        }
        $protocol = (require_once $this->requireFile);//执行

        $this->setProtocol($protocol);
        // check protocol class
        if (!$this->protocol) {
            throw new \Exception("[error] the protocol class  is empty or undefined");
        }

        if ($workerId >= $this->setting['worker_num']) {
            $this ->_setProcessName($this->processName . ': task worker process');
        } else {
            $this ->_setProcessName($this->processName . ': event worker process');
            //process timer server
            if (isset($this ->timers[$workerId])) {
                $runnable = $this ->timers[$workerId];
                require_once($runnable);
                $className = basename($runnable, '.php');
                $taskConf = ['workerId' => $workerId, 'className' => $className];
                $o = new $className($taskConf);
                if (method_exists($o, 'start')) {
                    $conf = ['path' => '/data/log/', 'loggerName' => $className, 'level' => $o->logLevel, 'decorators' => ['backtrace']];
                    Log::create($conf);
                    swoole_timer_tick($o->interval, function () use ($workerId, $runnable, $o) {
                        try {
                            call_user_func([$o, 'start']);
                        } catch (\Exception $e) {
                            Log::error("error in runnable: $runnable, worker id: $workerId, e: " . print_r($e, true));
                        }
                    });
                }
            }
        }

        $this->protocol->onStart($server, $workerId);
    }

    public function onConnect($server, $fd, $fromId)
    {
        // $this->log("Client connected : fd=$fd|fromId=$fromId");
        $this->protocol->onConnect($server, $fd, $fromId);
    }

    public function onTask($server, $taskId, $fromId, $data)
    {
        $this->protocol->onTask($server, $taskId, $fromId, $data);
    }

    public function onFinish($server, $taskId, $data)
    {
        $this->protocol->onFinish($server, $taskId, $data);
    }

    public function onClose($server, $fd, $fromId)
    {
        $this->protocol->onClose($server, $fd, $fromId);
    }

    public function onWorkerStop($server, $workerId) {
        $this->protocol->onShutdown($server, $workerId);
        if (isset($this ->timers[$workerId])) {
            $runnable = $this ->timers[$workerId];
            require_once($runnable);
            $className = basename($runnable, '.php');
            $taskConf = ['workerId' => $workerId, 'className' => $className];
            $o = new $className($taskConf);
            if (method_exists($o, 'onShutdown')) {
                call_user_func([$o, 'onShutdown']);
            }
        }
    }

    public function onTimer($server, $interval)
    {
        $this->protocol->onTimer($server, $interval);
    }

    public function onRequest($request, $response)
    {
        $req = new Request();
        $req->data = get_object_vars($request);
        if (isset($req->data['zcookie'])) {
            $req->data['cookie'] = $req->data['zcookie'];
            unset($req->data['zcookie']);
        }
        if(empty($req->data['post'])){
            $req->data['post']=$request->rawContent();
        }

        $req->servType = $this->servType;
        $req->server = $this->sw;

        $resp = new Response();
        $resp->servType = $this->servType;
        $resp->resource = $response;
        $resp->server = $this->sw;
        $this->protocol->onRequest($req, $resp);
    }

    public function onReceive($server, $fd, $fromId, $data)
    {
        $req = new Request();
        $req->buf = $data;
        $req->servType = $this->servType;
        $req->server = $server;

        $resp = new Response();
        $resp->fd = $fd;
        $resp->from_fd = $fromId;
        $resp->servType = $this->servType;
        $resp->server = $server;

        $this->protocol->onRequest($req, $resp);

    }

    public function setProtocol($protocol)
    {
        if (!($protocol instanceof Event)) {
            throw new \Exception("[error] The protocol is not instanceof Event");
        }

        $this->protocol = $protocol;
    }

    public function run($setting = array())
    {
        echo __METHOD__ . PHP_EOL;
        $this->setting = array_merge($this->setting, $setting);
        $cmd = isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'help';
        $this->_initRunTime(); // 初始化server资源
        switch ($cmd) {
            //stop
            case 'stop':
                $this->shutdown();
                break;
            //start
            case 'start':
                $this->initServer();
                $this->start();
                break;
            //reload worker
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                sleep(2);
                $this->initServer();
                $this->start();
                break;
            case 'status':
                $this->status();
                break;
            default:
                echo 'Usage:php swoole.php start | stop | reload | restart | status | help' . PHP_EOL;
                break;
        }
    }


    protected function start()
    {

        $this->log($this->processName . ": start\033[31;40m [OK] \033[0m");
        $this->sw->start();
    }


    protected function shutdown()
    {
        $masterId = $this->getMasterPid();
        if (!$masterId) {
            $this->log("[warning] " . $this->processName . ": can not find master pid file");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        } elseif (!posix_kill($masterId, 15)) {
            $this->log("[warning] " . $this->processName . ": send signal to master failed");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
        unlink($this->masterPidFile);
        unlink($this->managerPidFile);
        usleep(50000);
        $this->log($this->processName . ": stop\033[31;40m [OK] \033[0m");
        return true;
    }

    protected function reload()
    {
        $managerId = $this->getManagerPid();
        if (!$managerId) {
            $this->log("[warning] " . $this->processName . ": can not find manager pid file");
            $this->log($this->processName . ": reload\033[31;40m [FAIL] \033[0m");
            return false;
        } elseif (!posix_kill($managerId, 10))//USR1
        {
            $this->log("[warning] " . $this->processName . ": send signal to manager failed");
            $this->log($this->processName . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
        $this->log($this->processName . ": reload\033[31;40m [OK] \033[0m");
        return true;
    }

    protected function  status()
    {
        $this->log("*****************************************************************");
        $this->log("Summary: ");
        $this->log("Swoole Version: " . SWOOLE_VERSION);
        if (!$this->checkServerIsRunning()) {
            $this->log($this->processName . ": is running \033[31;40m [FAIL] \033[0m");
            $this->log("*****************************************************************");
            return false;
        }
        $this->log($this->processName . ": is running \033[31;40m [OK] \033[0m");
        $this->log("master pid : is " . $this->getMasterPid());
        $this->log("manager pid : is " . $this->getManagerPid());
        $this->log("*****************************************************************");
    }

    protected function getMasterPid()
    {
        $pid = false;
        if (file_exists($this->masterPidFile)) {
            $pid = file_get_contents($this->masterPidFile);
        }
        return $pid;
    }

    protected function getManagerPid()
    {
        $pid = false;
        if (file_exists($this->managerPidFile)) {
            $pid = file_get_contents($this->managerPidFile);
        }
        return $pid;
    }

    protected function checkServerIsRunning()
    {
        $pid = $this->getMasterPid();
        return $pid && $this->checkPidIsRunning($pid);
    }

    protected function checkPidIsRunning($pid)
    {
        return posix_kill($pid, 0);
    }

    public function close($client_id)
    {
        swoole_server_close($this->sw, $client_id);
    }

    public function send($client_id, $data)
    {
        swoole_server_send($this->sw, $client_id, $data);
    }

    public function daemonize()
    {
        $this->setting['setting']['daemonize'] = 1;
    }

    protected function setHost()
    {
        $ipList = swoole_get_local_ip();
        if (isset($ipList['eth1'])) {
            $this->host = $ipList['eth1'];
        } elseif (isset($ipList['eth0'])) {
            $this->host = $ipList['eth0'];
        } else {
            $this->host = '0.0.0.0';
        }
    }

    public function log($msg)
    {
        if ($this->sw->setting['log_file'] && file_exists($this->sw->setting['log_file'])) {
            error_log($msg . PHP_EOL, 3, $this->sw->setting['log_file']);
        }
        echo $msg . PHP_EOL;
    }

    /**
     * 改变进程的用户ID
     * @param $user
     */
    public function changeUser($user)
    {
        if (!function_exists('posix_getpwnam')) {
            trigger_error(__METHOD__ . ": require posix extension.");
            return;
        }
        $user = posix_getpwnam($user);
        if ($user) {
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
        }
    }

    public function _setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__ . " failed. require cli_set_process_title or swoole_set_process_name.");
        }
    }

}


