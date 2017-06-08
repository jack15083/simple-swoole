<?php

namespace weblib\log;

/**
 * 日志类
 * Class log
 * @package weblib\LOg
 */
class Log
{
	// 日志存放路径
	protected static $logpath = '/data/log/service/';
	
	/**
	 * 设置日志路径
	 * @param $path
	 * @return bool
	 */
	public static function setLogpath($path = '') {
		if(!empty($path) && file_exists($path)) {
			self::$logpath = $path;
			return true;
		}
		else {
			return false;
		}
	}

    /**
     * 信息日志(用于请求出入的参数)
     * @param $msg
     * @param $r
     * @param $method
     */
    public static function info($msg, $r, $method, $df = null, $df2 = null)
    {
        $time = date('m-d H:i:s');
        $list = explode('::', $method);
        $class = $list[0];
        $method = $list[1];
        if (isset($df)) {
            $df = "[df={$df}]";
        }
        if (isset($df2)) {
            $df2 = "[df={$df2}]";
        }
        $msg = "[{$time}][info][{$method}][r={$r}]{$df}{$df2}" . $msg . "\n";


        error_log($msg, 3, self::$logpath . $class . '.log');
    }

    /**
     * 错误日志(所有失败的地方都必须记录)
     * @param $msg
     * @param $r
     * @param $method
     */
    public static function error($msg, $r, $method, $df = null, $df2 = null)
    {
        $time = date('m-d H:i:s');
        $list = explode('::', $method);
        $class = $list[0];
        $method = $list[1];
        if (isset($df)) {
            $df = "[df={$df}]";
        }
        if (isset($df2)) {
            $df2 = "[df={$df2}]";
        }
        $msg = "[{$time}][error][{$method}][r={$r}]{$df}{$df2}" . $msg . "\n";

        error_log($msg, 3, self::$logpath . $class . '.log');
    }

    /**
     * 调试用的信息日志
     * @param $msg
     * @param $r
     * @param $method
     */
    public static function debug($msg, $r, $method, $df = null, $df2 = null)
    {
        if(defined('APP_ENV') && (APP_ENV == 'ol' || APP_ENV == 'pre')) {
            return;
        }
        
        $time = date('m-d H:i:s');
        $list = explode('::', $method);
        $class = $list[0];
        $method = $list[1];
        if (isset($df)) {
            $df = "[df={$df}]";
        }
        if (isset($df2)) {
            $df2 = "[df={$df2}]";
        }
        $msg = "[{$time}][debug][{$method}][r={$r}]{$df}{$df2}" . $msg . "\n";

        error_log($msg, 3, self::$logpath . $class . '.log');
    }
}