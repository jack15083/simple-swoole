<?php
namespace frame\base;

use frame\log\Log;

class MysqlPool {
    const MAX_CONN = 50;
    const TIME_OUT = 100;
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
    public static function init($connkey, $max){
        if (empty(self::$config[$connkey]['is_init'])) {
            Log::info(__METHOD__ . " init start ");
            self::$config[$connkey]['max'] = $max;
            self::$config[$connkey]['is_init'] = true;   
            self::$working_pool[$connkey] = array();
            self::$free_queue[$connkey] = new \SplQueue();
            
            Log::info(print_r(self::$config, true));
            Log::info(print_r(self::$working_pool, true));
        }
        
    }

    public static function start($connkey, $argv){

        if (empty(self::$timer_start)) {
            Log::info(__METHOD__ . " schedule " . print_r($argv, true));
            //开启调度策略
            self::schedule($connkey, $argv);
            
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
        if($argv['max'] == 0) $argv['max'] = self::MAX_CONN;
        if($argv['timeout'] == 0) $argv['timeout'] = self::TIME_OUT;
        
        self::init($connkey, $argv['max']);
        self::start($connkey, $argv);

        if (!self::$free_queue[$connkey]->isEmpty()) {
            //现有资源可处于空闲状态
            $key = self::$free_queue[$connkey]->dequeue();
            Log::info(__METHOD__ . " free queue  key == $key ", __CLASS__);

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::update($connkey, $key, $argv), //更新一些标记字段
                );
        }

        elseif (count(self::$working_pool[$connkey]) < self::$config[$connkey]['max']) {
            Log::info(__METHOD__ . " below max ", __CLASS__);
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

        Log::info(__METHOD__ . " key == $key", __CLASS__);
        self::$free_queue[$connkey]->enqueue($key);
        self::$working_pool[$connkey][$key]['status'] = 0;
    }

    /**
     * [schedule 定时调度 更新过期资源]
     * @param timeout seconds
     * @return [type] [description]
     */
    public static function schedule($connkey, $argv){
        swoole_timer_tick($argv['timeout'], function() use($argv) {
            foreach (self::$working_pool as $connkey => $pool_data) {
                foreach ($pool_data as $key => $data) {
                    //当前连接已过期
                    if($data['lifetime'] < microtime(true)) {
                        //更新资源
                        $resource = $argv['db']->connect($argv['config']);
                        self::$working_pool[$connkey][$key]['obj'] = $resource;
                        self::$working_pool[$connkey][$key]['lifetime'] = microtime(true) + floatval($argv['timeout']);
                    }
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
}