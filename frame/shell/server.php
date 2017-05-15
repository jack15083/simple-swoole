<?php
/**
 * Created by PhpStorm, defined by markyuan.
 * User: markyuan
 * Date: 2015/6/19
 * Time: 21:06
 * Version: 1.0
 */

define('STARTBASEPATH', dirname(dirname(dirname(__FILE__))));
define('STARTSHELLPATH', STARTBASEPATH . '/tsf/shell/Start.php');

$cmds = array('start', 'stop', 'restart', 'list');
$name = isset($argv[1]) ? $argv[1] : '';
$cmd = isset($argv[2]) ? $argv[2] : '';   //cmd name
$cmd = empty($cmd) ? $name : $cmd;

switch($cmd){
    case 'list':
        listProcess();
        break;
    case 'start':
        start($name);
        break;
    case 'stop':
        stop($name);
        break;
    case 'restart':
        restart($name);
        break;
    default:
        opTips();
}

/**
 * 启动
 * @param $processName
 * @param $servType
 * @param $config
 * @param $srcIndex
 */
function start($name)
{
    $config = parse_ini_file(STARTBASEPATH . "/conf/{$name}.ini", true);
    $php = $config['server']['php'];
    $ret = getProcess($name);
    if($ret['exist'] === true){
        echo "{$name} start  \033[34;40m [FAIL] \033[0m process already exists" . PHP_EOL;
        exit;
    }

    //先处理单个 注意异常处理的情况
    $process = new swoole_process(function (swoole_process $worker) use ($name, $php) {//目前指支持一个
        $worker->exec($php, array(STARTSHELLPATH, 'start', $name));//拉起server
    }, false);
    $pid = $process->start();
    $exeRet = swoole_process::wait();
    if ($exeRet['code']) {//创建失败
        echo "{$name} start  \033[34;40m [FAIL] \033[0m" . PHP_EOL;
        exit;
    }

    //创建成功
    echo "{$name} start  \033[32;40m [SUCCESS] \033[0m" . PHP_EOL;
    $process->close();
}

/**
 * 停止
 * @param $processName
 */
function stop($name)
{
    $ret = getProcess($name);
    if($ret['exist'] === false){
        echo "{$name} stop  \033[34;40m [FAIL] \033[0m process not exists" . PHP_EOL;
        return;
    }

    $pidList = implode(' ', $ret['pidList']);
    $cmd = "kill -9 {$pidList}";
    exec($cmd, $output, $r);

    if ($r === false) { // kill失败时
        echo "{$name} stop  \033[34;40m [FAIL] \033[0m posix exec fail" . PHP_EOL;
        exit;
    }

    echo "{$name} stop  \033[32;40m [SUCCESS] \033[0m" . PHP_EOL;
}

/**
 * 重启
 * @param $processName
 * @param $servType
 * @param $config
 * @param $srcIndex
 */
function restart($name)
{
    stop($name);
    start($name);
}

/**
 * 获取当前服务进程PID，exist===false不存在pid
 * @return array
 */
function getProcess($processName)
{
    $cmd = "ps aux | grep '" . $processName . ": master process' | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    $cmd = "ps aux | grep '" . $processName . ": manager process' | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    $cmd = "ps aux | grep '" . $processName . ": event worker process' | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    if (empty($ret)) {
        return [
                'exist' => false,
        ];
    } else {
        return [
                'exist' => true,
                'pidList' => $ret,
        ];
    }
}

//输出所有可以执行的server
function listProcess(){
    $configDir = STARTBASEPATH . "/conf/*.ini";
    $configArr = glob($configDir);
    // 配置名必须是servername
    echo '----------------------------' . PHP_EOL;
    echo "your server list：" . PHP_EOL;
    foreach ($configArr as $k => $v) {
        echo "\033[32;40m " . basename($v, '.ini') . " \033[0m" . PHP_EOL;
    };
    echo '----------------------------' . PHP_EOL;
    exit;
}

function getProcessName(){
    $configDir = STARTBASEPATH . "/conf/*.ini";
    $configArr = glob($configDir);
    $nameList = array();
    foreach ($configArr as $k => $v) {
        $nameList[] = basename($v, '.ini');
    }
    return $nameList;
}

// welcome
function opTips()
{
    echo "welcome to use zeng swoole framework!" . PHP_EOL;
    echo "support cmd: \033[31;40m [start stop restart list] \033[0m" . PHP_EOL;
    exit;
}