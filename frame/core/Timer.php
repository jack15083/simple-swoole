<?php
namespace frame\core;

use \frame\log\Log;

class Timer
{
    public static function add($timeout, $callback, $params, $repeat)
    {
		Log::info(__METHOD__ . " add timer " , __CLASS__);
		$func = 'swoole_timer_' . ($repeat === 0 ? 'after' : 'tick');
		return $func(1000 * $timeout, function() use($callback, $params) {
			call_user_func_array($callback, $params);
		});
	}


    public static function del($id)
    {
		Log::info(__METHOD__ . " del timer ", __CLASS__);
		swoole_timer_clear($id);
	}
}