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
    const TIMEOUT = 500;
    /**
     * [__construct 构造函数，初始化mysqli]
     * @param [type] $sqlConf [description]
     */
    public function __construct($config){
        $this->conf = $config;
        $this->db =  new Swoole\Coroutine\MySQL();
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
            $result = $this->db->query($this ->sql);
            if ($result === false)
            {
                if ($this ->db->errno == 2013 or $this->db->errno == 2006)
                {
                    $this->db->close();
                    $r = $this ->db->connect();
                    if ($r === true)
                    {
                        continue;
                    }
                }
            }
            break;
        }
    }
    
    public function connect() {
        $this->db->connect([
            'host' => $this->conf['host'],
            'port' => $this->conf['port'],
            'user' => $this->conf['user'],
            'password' => $this->conf['password'],
            'database' => $this->conf['database'],
            'timeout' => self::TIMEOUT,
            'charset' => $this->conf['charset'],
        ]);
    }
    
    public function getLastInertId() {      
        return $this->db->insert_id;
    }
    
    public function getLastError() {
        return $this->db->error;
    }
    
    public function getLastErrno() {
        return $this->db->errno;
    }

}



