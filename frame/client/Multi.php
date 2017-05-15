<?php

// 增加命名空间
namespace frame\client;


class Multi extends Base
{

    public $callList = array();    //IO请求列表
    public $callRsp = array();    //回报结果
    public $client_key;    //client的key
    public $key;    //multicall自己的key
    public $callback; //回调函数
    public $calltime;

    /**
     * [__construct 构造函数，初始化CLIENT_KEY]
     */
    public function __construct()
    {
        $this->client_key = 0;
    }

    /**
     * [addCall 添加IO CLIENT]
     * @param TestClient $client [description]
     */
    private function request(Base $client, $key = '')
    {
        /**
         * 判断用户是否设置了key，如果没有，代为设置，按照添加顺序来设置KEY
         */

        \frame\log\Log::info(__METHOD__ . "key == $key  client == " . print_r($client, true), __CLASS__);
        if (empty($key)) {
            $key = $this->client_key;
            $this->client_key++;
        }
        $client->setKey($key);

        $this->callList[] = $client;
    }

    /**
     * [call 一次性添加多个client]
     * @param  array  $calls [description]
     * @return [type]        [description]
     */
    public function call($calls = array()){

        /*
            calls = array(
                'key1' => client1,
                'key2' => client2;
            );
         */
        foreach ($calls as $key => $call) {
                
                $this ->request($call, $key);
            }

        return $this;
    }

    /**
     * [sendData 循环异步网络发包]
     * @param  callable $callback [description]
     * @return [type]             [description]
     */
    public function send(callable $callback)
    {
        \frame\log\Log::info(__METHOD__ . " callList = " . print_r($this->callList, true), __CLASS__);
        $this->callback = $callback;
        $this->calltime = microtime(true);
        for ($i = 0; $i < count($this->callList); $i++) {
            $client = $this->callList[$i];
            $client->send(array($this, 'recv'));
        }
    }

    /**
     * [packRsp 回调函数，收包，合包，回调]
     * @param  [type] $r          [description]
     * @param  [type] $client_key [description]
     * @param  [type] $data       [description]
     * @return [type]             [description]
     */
    public function recv($r, $client_key, $calltime, $data)
    {
        \frame\log\Log::info(__METHOD__ . " r = $r client_key = $client_key callList = " . count($this->callRsp), __CLASS__);

        $this->callRsp[$client_key] = array('r' => $r, 'calltime' => $calltime, 'data' => $data);
        //收包完成
        if (count($this->callRsp) == count($this->callList)) {
            \frame\log\Log::info(__METHOD__ . " get all the rsp ==== " . print_r($this->callRsp, true), __CLASS__);
            $this->calltime = microtime(true) - $this->calltime;
            call_user_func_array($this->callback, array('r' => 0, 'key' => '', 'calltime' => $calltime, 'data' => $this->callRsp));
        }
    }
}