<?php
namespace frame\base;

//用来配置用户自定义的路由规则 以及一些log级别等
class  UserConfig
{
    private $UserConf = array(
        'log' => array('path' => '/data/log/', 'loggerName' => 'httpServer', 'level' => 'info'),
        'register' => array(
            //      'init' => 'LongUrl2ShortInit',
            //    'protocol' => 'testProtocol',
            //      'route' => 'testRoute',
        ),
    );

    public function getConfig($val)
    {
        return $this->UserConf;
    }
    
    public function setConfig($config)
    {
        $this->UserConf = $config;
    }   

}

?>