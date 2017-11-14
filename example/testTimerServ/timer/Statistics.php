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