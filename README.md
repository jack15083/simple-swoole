# Zeng-Swoole-Framework
An php mvc framewrok base Swoole
# 安装
基于linux环境，运行需要先安装swoole 2.0扩展

支持PHP7 

支持数据库连接池

#运行
````
cd frame/shell

启动http服务

php server.php testHttpServ start 
````

# http测试站点配置
````
[server]

;添加所有配置


//所有的server信息

;服务类型
type = http

; 监听端口

listen[] = 10001


; 入口文件

root = '/data/git/Zeng-Swoole-Framework/example/httpsrc/index.php'

;init = ''

;用来支持多php版本

php = '/usr/bin/php'
;server_name='testserver'


[setting]

; worker进程数
worker_num = 24
; task进程数
task_worker_num = 0
; 转发模式
dispatch_mode = 2
; 守护进程
daemonize = 1
;post数据最大
package_max_length = 12582912
buffer_output_size = 12582912
; 系统日志
log_file = '/data/log/server/newapi/BusinessServ.log'
; 心跳  遍历所有连接 interval 内没有发送数据 强制关闭
heartbeat_check_interval = 60
heartbeat_idle_time = 600
;关闭Nagle合并算法，立即发送数据到客户端，提升http服务响应数据
open_tcp_nodelay = true
;关闭eof检测
open_eof_check = false
open_eof_split = false
````
#路由

example/httpsrc/route/web.php

````
$router->get('/test', 'TestController@actionTest');
$router->get('/test1', 'TestController@actionTest');
$router->get('/test.html', 'TestController@actionTest');
$router->get('/test/{username}/{id2}/{id3}', 'TestController@actionTest');
````

#控制器
example/httpsrc/controller

````
<?php

use weblib\log\Log;
use frame\base\Controller;

class TestController extends Controller
{

    public function actionHttptest()
    {
        Log::info("action http test", 10, __METHOD__);
        $model = new TestModel();
        $data = $model->httpTest();

        $this ->send(print_r($data, true));
    }

    public function actionTest() {
        $data = $this->request->data;

        $this->send(print_r($data, true));
    }
    
    public function actionDbtest() {
        Log::info("action db test", 22 , __METHOD__);
        $model = new TestModel();
        $data = $model->dbTest();
        $this->header("Content-Type", "text/html; charset=utf-8");
        $this ->send(print_r($data, true));
    }
    
    public function actionTestPool() {
        Log::info("action db test", 30);
        $model = new TestModel();
        $data = $model->mysqliTest();
        $this->header("Content-Type", "text/html; charset=utf-8");
        $this ->send(print_r($data, true));
    }

    public function actionTestState()
    {
        $this->display('test');
    }
}

````

#Model
````
<?php

use frame\log\Log;

class TestModel
{

    public function udpTest()
    {

        $host = '10.166.145.243';
        $port = 9905;
        $timeout = 5; //second
        $data = 'test';
        $rsp = (yield new frame\client\Udp($host, $port, $data, $timeout));

        if ($rsp['r'] == 0) {
            Log::info(__METHOD__ . "udp rsp successful");
        } else {
            Log::error(__METHOD__ . " udp rsp faield rsp ==" . print_r($rsp, true));
        }
        return $rsp;
    }
    
    public function tcpTest()
    {

        $host = '10.166.145.243';
        $port = 9805;
        $timeout = 5; //second
        $data = 'test';
        $rsp = (yield new frame\client\Tcp($host, $port, $data, $timeout));

        if ($rsp['r'] == 0) {
            Log::info(__METHOD__ . "tcp rsp successful");
        } else {
            Log::error(__METHOD__ . " tcp rsp faield rsp ==" . print_r($rsp, true));
        }
        return $rsp;
    }

    
    public function dbTest() 
    {
        $db = new \frame\client\Mysql(ENVConst::getDBConf());
        //$string = $db->escape("abc'efg\r\n");
        //Log::info(__METHOD__ . " escape string is " . $string);
        Log::info(print_r($db->db, true));
        Log::info(print_r(get_class_methods($db->db), true));
        $res = $db->doQuery("select * from pay_ads");
        $db->close();
        return $res;
    }
    
    public function mysqliTest()
    {
        try
        {
            $db = new \frame\database\dbObject(ENVConst::getDBConf());
            /* $test = array();
            for($i = 0; $i < 10; $i++)
            {
                $test[$i] = new \frame\database\dbObject('users1', ENVConst::getDBConf());
            } */
            //$string = $db->escape("abc'efg\r\n");
            //Log::info(__METHOD__ . " escape string is " . $string);
            $res = $db->query("select * from tch_teacher where id = 1");
            $db->free();
            return $res;
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function getSchools($areaId)
    {
         try
         {
             $db = new \frame\database\dbObject(ENVConst::getIkukoDBConf());

             $res = $db->query("SELECT * FROM T_SCHOOL_DEFINED WHERE SCHOOL_PLACE_ID = 325689 AND PHASE = 1");
             $db->free();
             return $res;
         }
         catch (\Exception $e)
         {
             Log::error($e->getMessage());
             return false;
         }
    }
    
    public function httpTest()
    {
        $postData = array();
        $url = "http://www.kaike.la";
        $hc = new frame\client\CURL();
        $header = array(
            'User-Agent' => "firefox-agent",
        );
        $hc->setHeaders($header);
        $res = $hc->get($url);
        /*while (true) {
            if(frame\client\Http::$rsp[$res->key])
                return frame\client\Http::$rsp[$res->key]
        }*/
        return $res;
    }

}
````

#Timer 定时器

/example/timeServ/timer

````

<?php
use weblib\log\Log;
class Statistics extends BaseTimer {

    public $redis;
    public static $running = false;
    public static $hasRun = true;
    public $mysqli;
    public $db;

	/**
     * [__construct 构造函数，设定轮训时间]
     * @param [type] $workerId [description]
     */
    public function __construct($taskConf)
    {
        //每隔5分钟
        parent::__construct($taskConf, 1000 * 60 * 5, 'info');
    }
	
	/**
     * [run 执行函数]
     * @return [type] [description]
     */
    public function run($subTaskId)
    {
        if(!$this->checkRun())
            return false;

        try
        {
            $this->db = new \frame\database\dbObject(\ENVConst::getDBConf());
            $this->mysqli = $this->db->mysqli();
        }
        catch (\Exception $e)
        {
            Log::error(__LINE__ . $e->getMessage(), 1, __METHOD__);
            return false;
        }

        Log::info('正在执行' . date("Y-m-d H:i:s"), 1, __METHOD__);
        $startTime = microtime(true);


        $this->db->free();
        $endTime = microtime(true);
        Log::info('执行结束' . date("Y-m-d H:i:s") . ' 运行耗时：' . ($endTime - $startTime) . 's 内存占用：' .
            memory_get_peak_usage() / 1024 . ' kb', 1, __METHOD__);

	}

    /**
     * 检查任务运行条件
     * @return bool
     */
	private function checkRun()
    {
        return true;
        $currentHour = (int) date("H");
        if($currentHour < 1)
        {
            self::$hasRun = false;
            return false;
        }

        if(self::$running || self::$hasRun)
        {
            return false;
        }

        return true;
    }

    /**
     * 执行sql
     * @param $sql
     * @return bool|mysqli_result
     */
    private function query($sql)
    {
        try
        {
            $res = $this->mysqli->query($sql);
            if (!$res && !empty($this->mysqli->errno)) {
                Log::error("Query Failed, ERRNO: " . $this->mysqli->errno . " (" . $this->mysqli->error . ")", $this->mysqli->errno, __METHOD__);
                if ($this->mysqli->errno == 2013 || $this->mysqli->errno == 2006) {
                    $this->db->retryConnect();
                    $this->mysqli = $this->db->mysqli();
                    $res = $this->mysqli->query($sql);
                }
            }
        }
        catch (\Exception $e)
        {
            Log::error(__LINE__. $e->getMessage(), 1, __METHOD__);
            return false;
        }

        if(empty($res))
        {
            Log::error('Query Failed ' . $sql, 1,__METHOD__);
        }

        return $res;
    }
    
}
````



