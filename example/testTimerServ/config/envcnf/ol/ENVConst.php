<?php

class ENVConst
{


    const NUM = 684767;     //常量

    public static function getDBConf()  //一些配置
    {
        return array(
            'host' => '10.0.0.49',
            'username' => 'root',
            'password' => 'mst112233',
            'db'=> 'kaikela_market',
            'port' => 3306,
            'prefix' => '',
            'charset' => 'utf8',
            'instance' => 'kaikela_market',
            'pool' => [
                'max' => 5, //最大连接数15
                'min' => 1, //最小连接数
                'timeout' => 30  //连接过期时间30S
            ]
        );
    }

    public static function getIkukoDBConf()
    {
        return array(
            'host' => '10.0.0.53',
            'username' => 'ikukouser',
            'password' => '111111',
            'db'=> 'ikukodev',
            'port' => 3306,
            'prefix' => '',
            'charset' => 'utf8',
            'instance' => 'ikukouser',
            'pool' => [
                'max' => 5, //最大连接数15
                'min' => 1, //最小连接数
                'timeout' => 30  //连接过期时间30S
            ]
        );
    }

} 