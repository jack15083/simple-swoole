<?php 
namespace frame\client;

use frame\base\MysqlPool;
use frame\log\Log;
class MysqliDB 
{
    public $mysqli;
    public $connkey;
    public $reskey;
    public $argv;
    
    public function __construct($connkey, $dbConfig) 
    {
        $this->argv['config'] = $dbConfig;
        $this->argv['timeout'] = $dbConfig['pool']['timeout'];
        $this->argv['max'] = $dbConfig['pool']['max'];
        $this->argv['min'] = $dbConfig['pool']['min'];
        $this->argv['db'] = $this;
        $this->getResource($connkey, $this->argv);
    }
    
    public function connect($dbConfig)
    {
        $mysqli = new \mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['db'], $dbConfig['port']);
        if ($mysqli->connect_error)
        {
            Log::error('mysql connect error ' . $mysqli->connect_error);
            //throw new \Exception('mysql connect error' . $mysqli->connect_error);
        }
        
        return $mysqli;
    }
    
    public function query($sql)
    {
        // retry twice
        for ($i = 0; $i < 2; $i++)
        {
            $result = $this->mysqli->query($sql);
            if (!$result) 
            {
                log::error('sql query fail: error ' . $this->mysqli->error . ' sql:' . $sql);
                //更新数据库连接
                if ($this->mysqli->errno == 2013 || $this->mysqli->errno == 2006)
                {
                    MysqlPool::updateConnect($this->connkey, $this->reskey, $this->argv);
                    continue;
                }                               
            }
            
            break;
        }
        
        //if(!$result) throw new \Exception('execute sql error');
        
        return $result;
    }
    
    public function autocommit($auto = true)
    {
        return $this->mysqli->autocommit($auto);
    }
    
    public function commit()
    {
        return $this->mysqli->commit();
    }
    
    public function roolback()
    {
        return $this->mysqli->rollback();
    }
    
    public function free()
    {
        MysqlPool::freeResource($this->connkey, $this->reskey);
    }
    
    private function getResource($connkey, $argv)
    {
        $res = MysqlPool::getResource($connkey, $argv);
        $this->connkey = $connkey;
        if($res['r'] == 0)
        {
            $this->reskey = $res['key'];
            $this->mysqli = $res['data'];
        }
        else
        {
            Log::error('get mysql resource error, connection key is ' . $connkey . ' key:' . $this->reskey);
            //throw new \Exception('get mysql resource error, connection key is ' . $connkey );
        }
    }
}