<?php
namespace frame\base;

use frame\log\Log;

class MysqlPool {
    const MAX_CONN = 100;
    const TIME_OUT = 1800;
    const MIN_CONN = 10; //最小连接
    const UPDATE_TIME_INTERVAL = 30000; //定时更新毫秒
    const RECOVERY_TIME_INTERVAL = 30000; //定时回收毫秒
    
    public static $working_pool;
    public static $free_queue;
    public static $config;
    public static $timer_start = false;

    /**
     * [init 连接池初始化 支持多个数据库连接池]
     * @param  [type] $connkey [description]
     * @param  [type] $max [description]
     * @return [type]      [description]
     */
    public static function init($connkey, $argv){
        if (empty(self::$config[$connkey]['is_init'])) {
            Log::debug(__METHOD__ . " init start ");
            self::$config[$connkey]['max'] = $argv['max'];
            self::$config[$connkey]['min'] = $argv['min'];
            self::$config[$connkey]['is_init'] = true;   
            self::$working_pool[$connkey] = array();
            self::$free_queue[$connkey] = new \SplQueue();

        }
        
    }

    public static function start($connkey, $argv){

        if (!self::$timer_start) {
            Log::debug(__METHOD__ . " schedule ");
            //定时更新过期资源
            self::schedule($connkey, $argv);
            //定时回收数据库连接资源
            self::recovery($connkey, $argv);
            self::$timer_start = true;
        }   
    }

    /**
     * [getResource 分配资源]
     * @param  [type] $callback [description]
     * @param  [type] $argv     [description]
     * @return [type]           [description]
     */
    public static function getResource($connkey, $argv){
        if(empty($argv['max'])) $argv['max'] = self::MAX_CONN;
        if(empty($argv['min'])) $argv['min'] = self::MIN_CONN;
        if(empty($argv['timeout'])) $argv['timeout'] = self::TIME_OUT;
        
        self::init($connkey, $argv);
        self::start($connkey, $argv);

        if (!self::$free_queue[$connkey]->isEmpty()) {
            //现有资源可处于空闲状态
            $key = self::$free_queue[$connkey]->dequeue();
            Log::debug(__METHOD__ . " free queue  key == $key ", __CLASS__);

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::update($connkey, $key, $argv), //更新一些标记字段
                );
        }

        elseif (count(self::$working_pool[$connkey]) < self::$config[$connkey]['max']) {
            Log::debug(__METHOD__ . " below max ", __CLASS__);
            //当前池可以再添加资源用于分配
            $key = count(self::$working_pool[$connkey]);
            $resource = self::product($argv);
            self::$working_pool[$connkey][$key] = self::product($argv);

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::$working_pool[$connkey][$key]['obj'],
                );
        }
        else{
            Log::error(__METHOD__ . " no resource can apply ", __CLASS__);           
            return array('r' => 1);
        }
        
    }

    /**
     * [freeResource 释放资源]
     * @param  [type] $argv [description]
     * @return [type]       [description]
     */
    public static function freeResource($connkey, $key){
        Log::debug(__METHOD__ . " key == $key", __CLASS__);
        self::$free_queue[$connkey]->enqueue($key);
        self::$working_pool[$connkey][$key]['status'] = 0;
    }

    /**
     * [schedule 定时调度 更新过期资源]
     * @param timeout seconds
     * @return [type] [description]
     */
    public static function schedule($connkey, $argv){
        Log::debug(__METHOD__ . 'schedule start:' . $argv['timeout']);
        swoole_timer_tick(self::UPDATE_TIME_INTERVAL, function() use($argv) {          
            Log::debug('schedule timer tick start');
            foreach (self::$working_pool as $connkey => $pool_data) {
                foreach ($pool_data as $key => $data) {
                    //当前连接已过期
                    if($data['lifetime'] < microtime(true)) {
                        //更新资源
                        self::udateConnect($connkey, $key, $argv);
                    }
                }
            }
        });
    }
     
    public static function recovery($connkey, $argv){
        Log::debug(__METHOD__ . 'recovery start:' . $argv['timeout']);
        swoole_timer_tick(self::RECOVERY_TIME_INTERVAL, function() use($argv) {
            Log::debug('recovery timer tick start');
            foreach (self::$free_queue as $connkey => $queue) {
                if($queue->isEmpty()) 
                    continue;               
                //空闲资源超过最小连接数
                for($i = self::MIN_CONN; $i <= $queue->count(); ) {
                    $key = $queue->dequeue();
                    //关闭数据库连接
                    self::$working_pool[$connkey][$key]['obj']->close();
                    unset(self::$working_pool[$connkey][$key]);
                }
            }
        });
    }
    /**
     * [product 生产资源]
     * @return [type] [description]
     */
    private static function product($argv){
        $resource = $argv['db']->connect($argv['config']);
        if(!$resource) return false;
        return array(
            'obj' => $resource,                                             //实例
            'lifetime' => microtime(true) + floatval($argv['timeout']),   //生命期
            'status' => 1,                                                //状态 1 在用 0 空闲
            );
    }

    /**
     * [update 更新资源]
     * @param  [type] $key  [description]
     * @param  [type] $argv [description]
     * @return [type]       [description]
     */
    private static function update($connkey, $key, $argv){

        self::$working_pool[$connkey][$key]['status'] = 1;
        self::$working_pool[$connkey][$key]['lifetime'] = microtime(true) + floatval($argv['timeout']);
        return self::$working_pool[$connkey][$key]['obj'];
    }
    
    /**
     * 更新数据库连接
     * @param unknown $connkey
     * @param unknown $key
     * @param unknown $obj
     */
    public static function updateConnect($connkey, $key, $argv)
    {
        //更新资源
        $argv['db']->close();
        $resource = $argv['db']->connect($argv['config']);
        self::$working_pool[$connkey][$key]['obj'] = $resource;
        self::$working_pool[$connkey][$key]['lifetime'] = microtime(true) + floatval($argv['timeout']);
        Log::info('更新working pool key:' . $connkey . $key);
    }
}