<?php

//读取配置，启动对应的server 根据传进来的server名字 已经知道协议类型
// 定义根目录
define('FRAMEWORKBASEPATH', dirname(__DIR__));
$loader = require dirname(FRAMEWORKBASEPATH) . '/vendor/autoload.php';
require_once dirname(FRAMEWORKBASEPATH) . '/frame/App.php';

//读取配置
$cmd  = $argv[1];   //cmd name
$name = $argv[2];
if (!$cmd || !$name) {
    echo "please input cmd and server name: start all,start testserv ";
    exit;
}

//自动加载引用类
spl_autoload_register(function ($class) {

    // what namespace prefix should be recognized?
    $prefix = '/^frame\\\(.*?)\\\/is';
    preg_match($prefix, $class, $matches);

    if(empty($matches[0]))
        return ;

    // strip the prefix off the class
    $class = substr($class, strlen($matches[0]));

    // a partial filename
    $part = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . $matches[1] . DIRECTORY_SEPARATOR . $part;

    if(!file_exists($file))
        return;

    require_once $file;

});

//读取配置文件 然后启动对应的server
$configPath = ((dirname(FRAMEWORKBASEPATH))) . '/conf/' . $name . '.ini';//获取配置地址

if (!file_exists($configPath)) {
    throw new \Exception("[error] profiles [$configPath] can not be loaded");
}
$config = parse_ini_file($configPath, true);
$loader->addClassMap(generateClassMapFiles(new RecursiveDirectoryIterator(dirname($config['server']['root']))));

//生成日志路径
$logDir = dirname($config['setting']['log_file']);
if(!is_dir($logDir))
{
    @mkdir($logDir);
}

$server = new frame\core\Server();
$server->servType = $config['server']['type'];
//合并config 只读一次
$server->config = array_merge($server->config, $config);
$server->setProcessName($name);
$server->setRequire($config['server']['root']);
$server->run();

//自动加载所能项目录内文件
function generateClassMapFiles($dir) {

    $files = array();

    for (; $dir->valid(); $dir->next()) {
        if ($dir->isDir() && !$dir->isDot()) {
            if ($dir->haschildren()) {
                $files = array_merge($files, generateClassMapFiles($dir->getChildren()));
            };
        }else if($dir->isFile()){
            $files[$dir->getBasename(".php")] = $dir->getPathName();
        }
    }
    return $files;
}