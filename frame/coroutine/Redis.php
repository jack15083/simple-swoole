<?php 
namespace frame\coroutine;

class redis 
{
    private $client;
    
    public function __construct($config) 
    {
        $this->client = new Swoole\Coroutine\Redis();
        if(empty($config['ip']) || empty($config['port'])) 
        {
            throw new \Exception('redis config error');
        }
        
        $this->client->connect($config['ip'], $config['port']);
        if(!empty($this->client->errCode)) 
        {
            throw new \Exception($this->client->errMsg, $this->client->errCode);
        }
    }
    
    public function getInstance() 
    {
        return $this->client;
    }
    
    public function get($key) 
    {
        $val = $this->client->get($key);
        if(!empty($this->client->errCode)) {
            throw new \Exception($this->client->errMsg, $this->client->errCode);
        }

        return $val;
        
    }
    
}