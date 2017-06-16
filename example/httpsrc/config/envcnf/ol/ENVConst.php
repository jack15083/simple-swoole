<?php

class ENVConst
{


    const NUM = 684767;     //常量

    public static function getDBConf()  //一些配置
    {
        return array(
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'jack1989',
            'db'=> 'users',
            'port' => 3306,
            'prefix' => '',
            'charset' => 'utf8',
            'instance' => 'users',
            'pool' => [
                'max' => 15, //最大连接数15
                'min' => 3, //最小连接数
                'timeout' => 30  //连接过期时间30S
            ]
        );
    }

} 