<?php

namespace frame\log;

use frame\log\Logger;
use Psr\Log\LogLevel;

class Log {

    private static $logger = null;
    private static $level = 'debug';
    private static $path = null;
    private static $loggerName = "app";
    private static $decorators = [];

    public static function create($conf) {
        self::setLogPath($conf['path']);
        self::setLoggerName($conf['loggerName']);
        self::setLogLevel($conf['level']);
        if (!empty($conf['decorators'])) {
            self::setDecorators(
                array_map(function ($name) {
                    $c = __NAMESPACE__ . '\\' . ucfirst($name) . 'Decorator';
                    return new $c();
                }, $conf['decorators'])
            );
        }
        self::init();
        return self::$logger;
    }

    private static function init() {
        $path = self::$path ?: sys_get_temp_dir() . "frame_log";
        self::$logger = new Logger(self::$level, self::$decorators, [new FileAppender(self::$loggerName, $path)]);
    }

    public static function setLogLevel($level) {
        self::$level = $level;
    }

    public static function setLogPath($path) {
        self::$path = $path;
    }

    public static function setLoggerName($loggerName) {
        self::$loggerName = $loggerName;
    }

    public static function setDecorators(array $decorators) {
        self::$decorators = $decorators;
    }

    public static function debug($message) {
        return self::log("debug", $message);
    }

    public static function info($message) {
        return self::log("info", $message);
    }

    public static function notice($message) {
        return self::log("notice", $message);
    }

    public static function warning($message) {
        return self::log("warning", $message);
    }

    public static function error($message) {
        return self::log("error", $message);
    }

    public static function critical($message) {
        return self::log("critical", $message);
    }

    public static function alert($message) {
        return self::log("alert", $message);
    }

    public static function emergency($message) {
        return self::log("emergency", $message);
    }

    private static function log($level, $message) {
        if (self::$logger == null) {
            self::init();
        }
        self::$logger -> $level($message);
        return true;
    }

}
	

