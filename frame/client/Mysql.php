<?php

namespace frame\client;

use frame\log\Log;
use frame\pool\MysqlPool;

class Mysql extends Base{

    public $key;
    protected $db;
    protected $sql;
    protected $conf;
    protected $callback;
    protected $calltime;

    private $db_sock;
    const ERROR = 1;
    const OK = 0;
    /**
     * [__construct 构造函数，初始化mysqli]
     * @param [type] $sqlConf [description]
     */
    public function __construct($sqlConf, $rsp){
        
        $this ->key = $rsp['key'];
        $this ->db = $rsp['data'];
        $this ->conf = $sqlConf;
    }


    /**
     * [send 兼容Base类封装的send方法，调度器可以不感知client类型]
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function send(callable $callback){

        $this ->callback = $callback;
        if (!isset($this ->db)) {
            
            Log::error(__METHOD__ . " db not init ", __CLASS__);
            $this ->callback(self::ERROR, 'db not init');
            return;
        }

        if (!isset($this ->db_sock)) {

            $config = $this ->conf;
            $this ->calltime = microtime(true);
            $this ->db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);

            if (!empty($config['charset']))
            {
                $this ->db->set_charset($config['charset']);
            }

            $this ->db_sock = swoole_get_mysqli_sock($this ->db);
            swoole_event_add($this ->db_sock, array($this, 'onSqlReady'));
        }

        $this ->doQuery($this ->sql);
    }

    /**
     * [query 使用者调用该接口，返回当前mysql实例]
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function query($sql){

        $this ->sql = $sql;
        return $this;
    }


    /**
     * [doQuery 异步查询，两次重试]
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function doQuery($sql){

        // retry twice
        for ($i = 0; $i < 2; $i++)
        {
            $result = $this ->db ->query($this ->sql, MYSQLI_ASYNC);
            if ($result === false)
            {
                if ($this ->db ->errno == 2013 or $this ->db ->errno == 2006)
                {
                    $this ->db ->close();
                    $r = $this ->db ->connect();
                    if ($r === true)
                    {
                        continue;
                    }
                }
            }
            break;
        }
    }

    /**
     * [onSqlReady eventloop异步回调函数]
     * @return [type] [description]
     */
    public function onSqlReady(){

        if ($result = $this ->db ->reap_async_query())
        {
            $this ->callback(self::OK, $result ->fetch_all());
            if (is_object($result))
            {
                mysqli_free_result($result);
            }
        }
        else
        {
            echo "MySQLi Error: " . mysqli_error($this ->db)."\n";
            $this ->callback(self::ERROR, mysqli_error($this ->db));
        }
    }

    public function close(){

        //Log::info(__METHOD__ ." del eventloop and close db and free resource", __CLASS__);
        swoole_event_del($this ->db_sock);
        $this ->db ->close();

        return MysqlPool::freeResource($this ->key);
    }

    /**
     * [callback 回调函数]
     * @param  [type]   $r        [description]
     * @param  [type]   $key      [description]
     * @param  [type]   $calltime [description]
     * @param  [type]   $data     [description]
     * @return function           [description]
     */
    private function callback($r, $data){

        $this ->calltime = $this ->calltime - microtime(true);
        call_user_func_array($this ->callback, array('r' => $r, 'key' => $this ->key, 'calltime' => $this ->calltime, 'data' => $data));
    }
}



