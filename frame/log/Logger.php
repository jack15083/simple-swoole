<?php

namespace frame\log;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger implements LoggerInterface {

    public static $levelNum = [
        'emergency' => 800,
        'alert' => 700,
        'critical' => 600,
        'error' => 500,
        'warning' => 400,
        'notice' => 300,
        'info' => 200,
        'debug' => 100
    ];

    private $currentLevel = 100;

    private $decorators;
    private $appenders;

    public function __construct($level = 'debug', array $decorators = [], array $appenders = []) {
        $this -> currentLevel = array_key_exists($level, self::$levelNum) ? self::$levelNum[$level] : 100;
        $this -> decorators = $decorators;
        $this -> appenders = $appenders ?: [new ConsoleAppender()];
        date_default_timezone_set('PRC');
    }

    private function makeMessage($level, $message) {
        $m = ['date' => date('Y-m-d H:i:s'), 'level' => strtoupper($level), '__text' => $message];
        foreach($this -> decorators as $d) {
            $d -> decorate($m);
        }
        return $m;
    }

    public function log($level, $message, array $context = array()) {
        if (self::isLoggerEnable($level)) {
            // PSR-3 states that $message should be a string
            $m = $this ->makeMessage($level, (string)$message);
            foreach($this -> appenders as $a) {
                if (!$a -> append($m)) break;
            }
        }
    }

    public function isLoggerEnable($level) {
        return $this -> currentLevel <= self::$levelNum[$level];
    }

}

