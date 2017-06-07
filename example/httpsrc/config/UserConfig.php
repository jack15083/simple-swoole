<?php

//用来配置用户自定义的路由规则 以及一些log级别等
class  UserConfig
{
    public static $UserConf = array(
        'log' => array('path' => '/data/log/', 'loggerName' => 'httpServer', 'level' => 'error'),
        'register' => array(
            //      'init' => 'LongUrl2ShortInit',
            //    'protocol' => 'testProtocol',
            //      'route' => 'testRoute',
        ),
    );

    public static function getConfig($val)
    {
        return self::$UserConf[$val];
    }

}

?>