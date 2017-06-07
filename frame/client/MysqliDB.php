<?php 
namespace frame\client;

use frame\base\MysqlPool;
use frame\log\Log;
class MysqliDB 
{
    public $mysqli;
    public $connkey;
    public $reskey;
    
    public function __construct($connkey, $dbConfig) 
    {
        $argv['config'] = $dbConfig;
        $argv['timeout'] = $dbConfig['pool']['timeout'];
        $argv['max'] = $dbConfig['pool']['max'];
        $argv['db'] = $this;
        $res = MysqlPool::getResource($connkey, $argv);
        $this->connkey = $connkey;
        if($res['r'] == 0) 
        {
            $this->reskey = $res['key'];
            $this->mysqli = $res['data'];      
        }
        else
        {
            Log::error('get mysql resource error, connection key is ' . $connkey);
            throw new \Exception('get mysql resource error, connection key is ' . $connkey );
        }
    }
    
    public function connect($dbConfig)
    {
        $mysqli = new \mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['db'], $dbConfig['port']);
        if ($this->mysqli->connect_error)
        {
            Log::error('mysql connect error ' . $mysqli->connect_error);
            return false;
        }
        
        return $mysqli;
    }
    
    public function query($sql)
    {
        $result = $this->mysqli->query($sql);
        return $result;
    }
    
    public function free()
    {
        MysqlPool::freeResource($this->connkey, $this->reskey);
    }
}