<?php

//读取配置，启动对应的server 根据传进来的server名字 已经知道协议类型
// 定义根目录
define('FRAMEWORKBASEPATH', dirname(dirname(__FILE__)));
$loader = require_once dirname(FRAMEWORKBASEPATH) . '/vendor/autoload.php';
require_once dirname(FRAMEWORKBASEPATH) . '/frame/App.php';

//读取配置
$cmd = $argv[1];   //cmd name
$name = $argv[2];
if (!$cmd || !$name) {
    echo "please input cmd and server name: start all,start testserv ";
    exit;
}
//读取配置文件 然后启动对应的server
$configPath = ((dirname(FRAMEWORKBASEPATH))) . '/conf/' . $name . '.ini';//获取配置地址
if (!file_exists($configPath)) {
    throw new \Exception("[error] profiles [$configPath] can not be loaded");
}
$config = parse_ini_file($configPath, true);

$loader->addClassMap(generateClassMapFiles(new RecursiveDirectoryIterator(dirname($config['server']['root']))));

$server = new \frame\core\Server();
$server->servType = $config['server']['type'];
//合并config 只读一次
$server->config = array_merge($server->config, $config);
$server->setProcessName($name);
$server->setRequire($config['server']['root']);
$server->run();

function generateClassMapFiles($dir) {

    $files = array();

    for (; $dir->valid(); $dir->next()) {
        if ($dir->isDir() && !$dir->isDot()) {
            if ($dir->haschildren()) {
                $files = array_merge($files, generateClassMapFiles($dir->getChildren()));
            };
        }else if($dir->isFile()){
            $files[$dir -> getBasename(".php")] = $dir->getPathName();
        }
    }
    return $files;
}