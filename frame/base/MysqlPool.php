<?php
/**
 * Mysql connection pool
 * @author zengfanwei
 */
namespace frame\base;

use frame\log\Log;

class MysqlPool
{
    const MAX_CONN = 100;
    const TIME_OUT = 1800;
    const MIN_CONN = 10; //最小连接
    const RECOVERY_TIME_INTERVAL = 30000; //定时回收毫秒

    public static $working_pool;
    public static $free_queue; //空闲连接资源队列
    public static $close_queue; //已关闭连接资源队列
    public static $config;
    public static $timer_start = false;

    /**
     * [init 连接池初始化 支持多个数据库连接池]
     * @param  $connkey 连接关键字，可以实现多个不同的数据库连接
     * @param  $argv [description] 配置参数
     */
    public static function init($connkey, $argv)
    {
        if (empty(self::$config[$connkey]['is_init'])) {
            Log::debug(__METHOD__ . " init start ");
            self::$config[$connkey]['max'] = $argv['max'];
            self::$config[$connkey]['min'] = $argv['min'];
            self::$config[$connkey]['is_init'] = true;
            self::$working_pool[$connkey] = array();
            self::$free_queue[$connkey] = new \SplQueue();
            self::$close_queue[$connkey] = new \SplQueue();
        }

    }

    /**
     * 开启定时任务
     * @param $connkey
     * @param $argv
     */
    public static function start($connkey, $argv)
    {

        if (!self::$timer_start) {
            Log::debug(__METHOD__ . " schedule ");
            //定时更新过期资源
            //self::schedule($connkey, $argv);
            //定时回收数据库连接资源
            self::recovery($connkey, $argv);
            self::$timer_start = true;
        }
    }

    /**
     * 获取连接资源
     * @param $connkey
     * @param $argv
     * @return array
     */
    public static function getResource($connkey, $argv)
    {
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
                'data' => self::update($connkey, $key, $argv),
            );
        } elseif (count(self::$working_pool[$connkey]) < self::$config[$connkey]['max']) {
            Log::debug(__METHOD__ . " below max, current count:" . count(self::$working_pool[$connkey]), __CLASS__);
            if(self::$close_queue[$connkey]->isEmpty()) {
                $key = count(self::$working_pool[$connkey]);
            }
            else {
                $key = self::$close_queue[$connkey]->dequeue();
            }

            //当前池可以再添加资源用于分配
            $resource = self::product($connkey, $argv);
            //product失败
            if(!$resource) {
                Log::info('product resource error:' . $connkey . $key);
                return array('r' => 1);
            }

            self::$working_pool[$connkey][$key] = $resource;

            return array(
                'r' => 0,
                'key' => $key,
                'data' => self::$working_pool[$connkey][$key]['obj'],
            );
        } else {
            Log::error(__METHOD__ . " no resource can apply ", __CLASS__);
            return array('r' => 1);
        }

    }

    /**
     * [freeResource 释放资源]
     * @param $connkey
     * @param $key
     */
    public static function freeResource($connkey, $key)
    {
        Log::debug(__METHOD__ . " key == $key", __CLASS__);
        self::$free_queue[$connkey]->enqueue($key);
        self::$working_pool[$connkey][$key]['status'] = 0;
    }

    /**
     * [schedule 定时调度 更新过期资源]
     * @param $connkey
     * @param $argv
     */
    public static function schedule($connkey, $argv)
    {
        Log::debug(__METHOD__ . 'schedule start:' . $argv['timeout'] . 's');
        swoole_timer_tick($argv['timeout'] * 1000, function() use($argv)
        {
            Log::debug('schedule timer tick start');
            foreach (self::$working_pool as $connkey => $pool_data)
            {
                foreach ($pool_data as $key => $data)
                {
                    //当前连接已超时
                    if($data['status'] != 0 && $data['lifetime'] < microtime(true))
                    {
                        //释放资源
                        self::freeResource($connkey, $key);
                    }
                }
            }
        });
    }

    /**
     * 定时回收多余空闲连接资源
     * @param string $connkey
     * @param array $argv
     */
    public static function recovery($connkey, $argv)
    {
        Log::debug(__METHOD__ . 'recovery start:' . self::RECOVERY_TIME_INTERVAL);
        swoole_timer_tick(self::RECOVERY_TIME_INTERVAL, function() use($argv) {
            Log::debug('recovery timer tick start');
            foreach (self::$free_queue as $connkey => $queue) {
                if($queue->isEmpty() || $queue->count() <= $argv['min'])
                    continue;
                //空闲资源超过最小连接，关闭多余的数据库连接
                for($i = $argv['min']; $i < $queue->count();) {
                    $key = $queue->dequeue();
                    //关闭数据库连接
                    self::$working_pool[$connkey][$key]['obj']->close();
                    self::$close_queue[$connkey]->enqueue($key);
                    unset(self::$working_pool[$connkey][$key]);
                    Log::debug(__METHOD__ . ' key' . $key . ' queue count:' . $queue->count() . ' connect number:' . count(self::$working_pool[$connkey]));
                }
            }
        });
    }

    /**
     * [product 生产资源]
     * @param $connkey
     * @param $argv
     */
    private static function product($connkey, $argv)
    {
        //防止并发出现已超过连接数
        if(count(self::$working_pool[$connkey]) >= self::$config[$connkey]['max'])
            return false;

        $resource = $argv['db']->connect($argv['config']);
        if(!$resource) return false;
        return array(
            'obj' => $resource,                                             //实例
            'lifetime' => microtime(true) + ((float) $argv['timeout']),   //生命期
            'status' => 1,                                                //状态 1 在用 0 空闲
        );
    }

    /**
     * [update 更新资源]
     * @param string $connkey
     * @param string $key
     * @param array $argv
     */
    private static function update($connkey, $key, $argv)
    {
        self::$working_pool[$connkey][$key]['status'] = 1;
        self::$working_pool[$connkey][$key]['lifetime'] = microtime(true) + ((float) $argv['timeout']);
        return self::$working_pool[$connkey][$key]['obj'];
    }

    /**
     * 更新数据库连接
     * @param string $connkey
     * @param string $key
     * @param array $argv
     */
    public static function updateConnect($connkey, $key, $argv)
    {
        //更新资源
        $argv['db']->close();
        $resource = $argv['db']->connect($argv['config']);
        self::$working_pool[$connkey][$key]['obj'] = $resource;
        self::$working_pool[$connkey][$key]['lifetime'] = microtime(true) + ((float) $argv['timeout']);
        Log::info('更新working pool key:' . $connkey . $key);
        return $resource;
    }
}