<?php

use frame\log\Log;

class TestModel
{


    /*
    array('r' => 0,
          'calltime' => 0.006115198135376,
          'data' => Array(),
    );
    */
    public function udpTest()
    {

        $host = '10.166.145.243';
        $port = 9905;
        $timeout = 5; //second
        $data = 'test';
        $rsp = (yield new frame\client\Udp($host, $port, $data, $timeout));

        if ($rsp['r'] == 0) {
            Log::info(__METHOD__ . "udp rsp successful");
        } else {
            Log::error(__METHOD__ . " udp rsp faield rsp ==" . print_r($rsp, true));
        }
        return $rsp;
    }

    /*
       array('r' => 0,
             'calltime' => 0.006115198135376,
             'data' => Array(),
            );
    */
    public function tcpTest()
    {

        $host = '10.166.145.243';
        $port = 9805;
        $timeout = 5; //second
        $data = 'test';
        $rsp = (yield new frame\client\Tcp($host, $port, $data, $timeout));

        if ($rsp['r'] == 0) {
            Log::info(__METHOD__ . "tcp rsp successful");
        } else {
            Log::error(__METHOD__ . " tcp rsp faield rsp ==" . print_r($rsp, true));
        }
        return $rsp;
    }

    public function mysqlTest()
    {
//        $sql = new \frame\client\Mysql(array('host' => '127.0.0.1', 'port' => 3345, 'user' => 'root', 'password' => 'root', 'database' => 'test', 'charset' => 'utf-8',));
//        $ret = (yield $sql ->query('show tables'));
//        var_dump($ret);
//        $ret = (yield $sql ->query('desc test'));
//        var_dump($ret);
    }
    
    public function dbTest() 
    {
        $db = new \frame\client\mysql(ENVConst::getDBConf());
        $db = frame\client\Mysql::getInstance('users');
        $res = $db->query("show tables");
        return $res;
    }

    /*http 返回值
     *
     *
     *
    ([r] => 0
    [calltime] => 0.006115198135376
    [data] => Array
        (
            [head] => Array
                (
                    [msg] => OK
                    [status] => 200
                    [protocol] => HTTP/1.1
                    [X-Powered-By] => koa
                    [Content-Type] => text/html; charset=utf-8
                    [Content-Length] => 163
                    [Date] => Thu, 20 Aug 2015 08:45:10 GMT
                    [Connection] => keep-alive
                )

            [body] => 'HAHA'
        )
     )
     *
     *
     *
     */

    public function httpTest()
    {
        $postData = array();
        $url = "http://www.baidu.com/test";
        $hc = new frame\client\Http($url);
        $hc->setTimeout(30);// 以秒为单位 设置长一些 有些请求会超时
        $header = array(
            'User-Agent' => "xxxxx-agent",
        );
        $res = $hc->post($postData, $header);
        return $res;
    }

}