<?php

namespace frame\log;

class FileAppender extends Appender {

    private $path;
    private $loggerName;

    function __construct($loggerName, $path, Formatter $formatter = null){
        parent::__construct($formatter);
        $this -> loggerName = $loggerName;
        $this -> path = $path;
        if (!is_dir($this -> path)) {
            if (!mkdir($this -> path, 0777, true)) {
                throw new \RuntimeException("create dir failed: " . $this -> path);
            }
        }
        date_default_timezone_set('PRC');
    }

    public function append(array $message){
        $file = $this -> path . DIRECTORY_SEPARATOR . $this -> loggerName . '_' . date('Ymd') . '.log';
        $line = $this ->formatter ->format($message);
        error_log($line, 3, $file);
        return true;
    }

} 