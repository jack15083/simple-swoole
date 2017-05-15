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
        yield $rsp;
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
        yield $rsp;
    }

    public function mysqlTest()
    {
//        $sql = new \frame\client\Mysql(array('host' => '127.0.0.1', 'port' => 3345, 'user' => 'root', 'password' => 'root', 'database' => 'test', 'charset' => 'utf-8',));
//        $ret = (yield $sql ->query('show tables'));
//        var_dump($ret);
//        $ret = (yield $sql ->query('desc test'));
//        var_dump($ret);
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
        $url = "http://10.xx.xx.xx:8893/test";
        $hc = new frame\client\Http($url);
        $hc->setTimeout(30);// 以秒为单位 设置长一些 有些请求会超时
        $header = array(
            'User-Agent' => "xxxxx-agent",
        );
        $res = (yield $hc->post($postData, $header));
        yield $res;
    }

    public function udsTest()
    {

        $ip = '10.130.151.80';
        $port = 8123;
        $uds = new \frame\db\udl\Uds($ip, $port);
        $sql = "SELECT FUin FROM db_crm3_mp.t_admin_gray_list WHERE FUin='822978945'";
        yield $uds ->query($sql,0);
    }


    /* mutical return value structure
        array('r' => 0,
              'calltime' => 22,
              'data' => array(
                        'key1' => array(
                                        'r' => 0,
                                        'calltime' => 1,
                                        'data' => array()
                                        ),
                        'key2' => array(
                                        'r' => 0,
                                        'calltime' => 1,
                                        'data' => array()
                                        ),

                            )
        );
    */

    public function multiTest()
    {
        $ip = '127.0.0.1';
        $data = 'test';
        $timeout = 0.5; //second
        $multi = new \frame\client\Multi();
        $firstReq = new \frame\client\Tcp($ip, '9905', $data, $timeout);
        $secondReq = new \frame\client\Udp($ip, '9904', $data, $timeout);
        $postData = array();
        $url = "http://10.xx.xx.xx:8893/test";
        $hc = new frame\client\Http($url);
        $hc->setTimeout(30);// 以秒为单位 设置长一些 有些请求会超时
        $header = array(
            'User-Agent' => "xxxxx-agent",
        );
        $thirdReq = $hc->post($postData, $header);
        
        $calls = array(
            'first' => $firstReq,
            'secondReq' => $secondReq,
            'thirdReq' => $thirdReq,
            );
        yield $multi ->calls($calls);

    }
}