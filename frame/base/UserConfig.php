<?php
/**
 * 用来配置用户自定义的路由规则 以及一些log级别等
 * @author zengfanwei
 */
namespace frame\base;

class  UserConfig
{
    private $UserConf = array(
        'log' => array('path' => '/data/logs/server', 'loggerName' => 'httpServer', 'level' => 'error'),
        'register' => array(
            //      'init' => 'LongUrl2ShortInit',
            //    'protocol' => 'testProtocol',
            //      'route' => 'testRoute',
        ),
    );

    public function getConfig($val)
    {
        return $this->UserConf[$val];
    }
    
    public function setConfig($config)
    {
        $this->UserConf = $config;
    }   

}

