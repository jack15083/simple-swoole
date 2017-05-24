<?php
namespace frame\base;

use frame\log\Log;

class MysqlPool {

    public static $working_pool;
    public static $free_queue;

    public static $max;
    public static $min;

    public static $is_start;

    /**
     * [init 连接池初始化]
     * @param  [type] $min [description]
     * @param  [type] $max [description]
     * @return [type]      [description]
     */
    public static function init($min, $max){

        self::$max = $max;
        self::$min = $min;
        self::$is_start = false;

        self::$working_pool = array();
        self::$free_queue = new \SplQueue();


    }

    public static function start(){

        if (!self::$is_start) {
            Log::info(__METHOD__ . " schedule ", __CLASS__);
            //开启调度策略
            self::schedule();

            //开启回收策略
            //self::unsetResource();
            
            self::$is_start = true;
        }   
    }

    /**
     * [getResource 分配资源]
     * @param  [type] $callback [description]
     * @param  [type] $argv     [description]
     * @return [type]           [description]
     */
    public static function getResource($arg){

        self::start();

        if (!self::$free_queue ->isEmpty()) {
            /*
                现有资源可处于空闲状态
             */
            $key = self::$free_queue ->dequeue();
            Log::info(__METHOD__ . " free queue  key == $key ", __CLASS__);

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::update($key, $argv), //更新一些标记字段
                );
        }

        elseif (count(self::$working_pool) < self::$max) {
            Log::info(__METHOD__ . " below max ", __CLASS__);
            //当前池可以再添加资源用于分配
            $key = count(self::$working_pool);
            self::$working_pool[$key] = self::product($argv);

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::$working_pool[$key]['obj'],
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
    public static function freeResource($key){

        Log::info(__METHOD__ . " key == $key", __CLASS__);
        self::$free_queue ->enqueue($key);
        self::$working_pool[$key]['status'] = 0;
    }

    /**
     * [schedule 定时调度]
     * @return [type] [description]
     */
    public static function schedule(){
        $i = 0;
        while ($i < 10) {
            ;
        }
    }

    /**
     * [product 生产资源]
     * @return [type] [description]
     */
    private static function product($argv){
        $client = $argv['client'];
        $client->db->connect($argv['config']);
        return array(
            'obj' => $client,                                             //实例
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
    private static function update($key, $argv){

        self::$working_pool[$key]['status'] = 1;
        self::$working_pool[$key]['lifetime'] = microtime(true) + floatval($argv['timeout']);
        return self::$working_pool[$key]['obj'];
    }
}