<?php

use frame\log\Log;

class TestModel
{

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

    
    public function dbTest() 
    {
        $db = new \frame\client\Mysql(ENVConst::getDBConf());
        //$string = $db->escape("abc'efg\r\n");
        //Log::info(__METHOD__ . " escape string is " . $string);
        Log::info(print_r($db->db, true));
        Log::info(print_r(get_class_methods($db->db), true));
        $res = $db->doQuery("select * from pay_ads");
        $db->close();
        return $res;
    }
    
    public function mysqliTest()
    {
        try
        {
            $db = new \frame\database\dbObject('users1', ENVConst::getDBConf());
            /* $test = array();
            for($i = 0; $i < 10; $i++)
            {
                $test[$i] = new \frame\database\dbObject('users1', ENVConst::getDBConf());
            } */
            //$string = $db->escape("abc'efg\r\n");
            //Log::info(__METHOD__ . " escape string is " . $string);
            $res = $db->query("select * from tch_teacher");
            $db->free();
            Log::info(print_r($res, true));
            return $res;
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
            return false;
        }
    }

    
    public function httpTest()
    {
        $postData = array();
        $url = "http://www.baidu.com";
        $hc = new frame\client\Http($url);
        $hc->setTimeout(30);// 以秒为单位 设置长一些 有些请求会超时
        $header = array(
            'User-Agent' => "firefox-agent",
        );
        $res = $hc->get([], $header);
        return $res;
    }

}