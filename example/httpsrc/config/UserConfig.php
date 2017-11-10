<?php
//用来配置用户自定义的路由规则 以及一些log级别等
return [
    'log' => array('path' => '/data/logs/server', 'loggerName' => 'testHttpServer', 'level' => 'debug'),
    'register' => array(
        //    'init' => 'LongUrl2ShortInit',
        //    'protocol' => 'testProtocol',
        //    'route' => 'testRoute',
    ),
];
