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
        //按班级从下往上计算
        $fetchClassSql = "select school_id, grade, class from home_member_child  where grade > " . (date("Y") - 2) . ' group by school_id, grade, class';
        $result = $this->query($fetchClassSql);
        $staticsDate = $this->getStaticsDate();

        while ($row = $result->fetch_assoc())
        {
            Log::info('start loop', 1,__METHOD__);
            $schoolId = $row['school_id'];
            $grade = (int) $row['grade'];
            $class = (int) $row['class'];

            Log::debug(print_r($row, true), 1, __METHOD__);
            if(!$schoolId || !$grade || !$class)
                continue;

            $keyClass = 'market_statistics_' . $schoolId . '_' . $grade . '_' . $class; //班级的key

            //查询班级所有家长
            $fetchMemberIdSql = "select hmc.member_id, hm.district, hm.province, hm.city from home_member_child hmc 
                left join home_member hm  on hmc.member_id = hm.id
                where hmc.school_id = $schoolId and hmc.grade = $grade and hmc.class = '$class' ";

            $resultMember = $this->query($fetchMemberIdSql);
            $memberIds = [];
            //$memberData = [];

            $i = $provinceId = $cityId = $areaId = 0;
            while ($rowMember = $resultMember->fetch_assoc())
            {
                Log::info('start loop member' . print_r($rowMember, true), 1,__METHOD__);
                if($i == 0)
                {
                    $provinceId = $rowMember['province'];
                    $cityId   = $rowMember['city'];
                    $areaId   = $rowMember['district'];
                }

                $memberId = $rowMember['member_id'];
                $memberIds[] = $memberId;
                $i++;
            }

            if(empty($memberIds) || !$provinceId || !$cityId || ! $areaId)
            {
                continue;
            }


            $memberIdsStr = implode(',',$memberIds);
            $fetchClassDataSql = "select * from home_play_data where member_id in ($memberIdsStr)";
            $resultView = $this->query($fetchClassDataSql);

            $default = [
                'play_time' => 0,  //总观看时长
                'play_time_distinct' => 0,
                'course' => 0, //完成课节数
                'parents' => 0
            ];

            $ret['total']    = $ret['lastMonth'] = $ret['lastWeek'] = $ret['curWeek'] = $default;
            $course = $parents = [
                'total' => [],
                'lastMonth' => [],
                'lastWeek' => [],
                'curWeek' => [],
            ];

            //计算观看总时长，及完成课节数
            while ($rowView = $resultView->fetch_assoc())
            {
                $this->getMemberViewData('lastMonth', $rowView, $staticsDate, $course, $ret, $parents);
                $this->getMemberViewData('lastWeek', $rowView, $staticsDate, $course, $ret, $parents);
                $this->getMemberViewData('total', $rowView, $staticsDate, $course, $ret, $parents);
                $this->getMemberViewData('curWeek', $rowView, $staticsDate, $course, $ret, $parents);
            }

            $savedata['play_time']            = $ret['total']['play_time_distinct'];
            $savedata['last_month_play_time'] = $ret['lastMonth']['play_time_distinct'];
            $savedata['last_week_play_time']  = $ret['lastWeek']['play_time_distinct'];
            $savedata['cur_week_play_time']   = $ret['curWeek']['play_time_distinct'];

            $savedata['complete_lessons']            = $ret['total']['course'];
            $savedata['last_month_complete_lessons'] = $ret['lastMonth']['course'];
            $savedata['last_week_complete_lessons']  = $ret['lastWeek']['course'];
            $savedata['cur_week_complete_lessons']   = $ret['curWeek']['course'];

            $savedata['member_number']             = $ret['total']['parents'];
            $savedata['last_month_member_number']  = $ret['lastMonth']['parents'];
            $savedata['last_week_member_number']   = $ret['lastWeek']['parents'];
            $savedata['cur_week_member_number']    = $ret['curWeek']['parents'];

            $savedata['update_time']        = microtime(true);
            $savedata['province_id']        = $provinceId;
            $savedata['city_id']            = $cityId;
            $savedata['area_id']            = $areaId;
            $savedata['grade']              = $grade;
            $savedata['class']              = $class;
            $savedata['school_id']          = $schoolId;

            //$this->save($savedata);
            $multiData[] = $savedata;
            $this->saveMulti($multiData);
        }

        $this->saveMulti($multiData, true);

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
     * 保存单条统计数据
     */
	private function save($data)
    {
        $keys = array_keys($data);
        $sql = 'select id from home_play_statistics where school_id = ' . $data['school_id'] . ' and grade=' . $data['grade'] . ' and class=' . $data['class'];
        $res = $this->query($sql);
        if($res->fetch_assoc())
        {
            $updateString = '';
            foreach ($data as $key => $value)
            {
                $updateString .= "`$key` = " . $value . ",";
            }

            $updateString = preg_replace('/,$/', '', $updateString);

            $updateSql = 'update home_play_statistics set ' . $updateString . ' where school_id = ' . $data['school_id'] .
                ' and grade=' . $data['grade'] . ' and class=' . $data['class'];

            $this->query($updateSql);
            return;
        }


        $sqlKeys   = '(' . implode(',', $keys) . ')';
        $sqlValues = '(' . implode(',', $data) . ')';
        $insertSql = 'INSERT INTO `home_play_statistics` ' . $sqlKeys . ' values ' . $sqlValues;

        $this->query($insertSql);
    }

    /**
     * 保存多条
     * @param $data
     * @param bool $end
     */
    private function saveMulti(&$data, $end = false)
    {
        if(empty($data)) return;

        if(count($data) != 2000 && !$end)
            return;

        $row = $data[0];
        $keys = array_keys($row);
        $updateString = '';
        foreach ($keys as $key)
        {
            $updateString .= "`$key` = VALUES(" . $key . "),";
        }

        $updateString = preg_replace('/,$/', '', $updateString);
        $insertKeys   = '(' . implode(',', $keys) . ')';

        $arr = [];
        foreach ($data as $key => $row)
        {
            $arr[$key] = '(' . implode(',', $row) . ')';
        }

        $values = implode(',', $arr);

        $insertSql = 'INSERT INTO `home_play_statistics` ' . $insertKeys . ' values ' . $values . ' ON DUPLICATE KEY UPDATE ' . $updateString;
        $this->query($insertSql);
        $data = [];
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

    /**
     * 获取统计需要的日期
     * @return mixed
     */
	private function getStaticsDate()
    {
        $year  = date("Y");
        $month = date("m");
        $day   = date("d");
        $week  = date('w');
        $date['lastMonth'] = $this->getLastMonth($year, $month);
        $date['lastWeekFirstDay'] = date("Ymd",mktime(0, 0 , 0,$month,$day - $week + 1 - 7, $year));
        $date['lastWeekLastDay'] = date("Ymd",mktime(23, 59, 59, $month, $day - $week + 7 - 7,$year));

        $date['curWeekFirstDay'] =  date("Ymd",mktime(0, 0 , 0,$month,$day - $week + 1, $year));
        $date['curWeekLastDay']  = date("Ymd",mktime(23,59,59,$month,$day - $week + 7, $year));
        return $date;
    }

    /**
     * 计算个人观看数据
     * @param $type
     * @param $data
     * @param $date
     * @param $course
     * @param $ret
     * @param $parents
     */
	private function getMemberViewData($type, $data, $date, &$course, &$ret, &$parents)
    {
        if($type == 'lastMonth' && $data['month'] != $date['lastMonth'])
            return;

        if($data['day'] < 10)
        {
            $data['day'] = '0' . $data['day'];
        }

        $Ymd = $data['month'] . $data['day'];
        if($type == 'lastWeek' && ($Ymd > $date['lastWeekLastDay'] || $Ymd < $date['lastWeekFirstDay']) )
            return;

        if($type == 'curWeek' && ($Ymd > $date['curWeekLastDay'] || $Ymd < $date['curWeekFirstDay']) )
            return;

        $ret[$type]['play_time'] += $data['play_time'];
        $ret[$type]['play_time_distinct'] += $data['play_time_distinct'];

        if(!isset($course[$data['course_id'] . '-' . $data['course_lesson_id']]))
        {
            $course[$data['course_id'] . '-' . $data['course_lesson_id']] = 0;
        }

        $course[$data['course_id'] . '-' . $data['course_lesson_id']] += $data['play_time_distinct'];

        //观看时长大于课程总时长算完成
        if(($course[$data['course_id'] . '-' . $data['course_lesson_id']]) / $data['total_time'] >= 0.9)
        {
            $ret[$type]['course']++;
        }

        if(!isset($parents[$data['member_id']]))
            $ret[$type]['parents']++;
    }

    /**
     * 上个月
     * @param $year
     * @param $currentMonth
     * @return int|string
     */
	public function getLastMonth($year, $currentMonth)
    {
        if($currentMonth == 1)
        {
            $year = $year - 1;
            $lastMonth = 12;
        }
        else
        {
            $lastMonth = $currentMonth - 1;
        }

        if($lastMonth < 10)
        {
            $lastMonth = '0' . $lastMonth;
        }

        return intval($year . $lastMonth);
    }
	
	/**
	 * @param unknown $dir
	 * @param unknown $ex
	 */
	public function delDir($dir, $ex = array())
	{
	    //先删除目录下的文件：
	    $dh = opendir($dir);
	
	    while ($file = readdir($dh))
	    {	
	        if($file != "." && $file != "..")
	        {
	            $fullpath = $dir . "/" . $file;
	
	            if(!is_dir($fullpath))
	            {
	                if(!isset($ex[$file]))
	                   unlink($fullpath);	
	            }
	            else
	            {
	                $this->delDir($fullpath);
	            }
	
	        }
	
	    }
	
	    closedir($dh);	
	    return false;	
	}
}