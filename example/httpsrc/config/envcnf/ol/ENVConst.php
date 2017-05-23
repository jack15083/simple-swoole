<?php

class ENVConst
{


    const NUM = 684767;     //常量

    public static function getDBConf()  //一些配置
    {
        return array(
            'host' => '127.0.0.1',
            'username' => 'debian-sys-maint',
            'password' => 'FgRRtKx8UVXpSI7c',
            'db'=> 'users',
            'port' => 3306,
            'prefix' => '',
            'charset' => 'utf8',
            'instance' => 'users',
        );
    }

} 