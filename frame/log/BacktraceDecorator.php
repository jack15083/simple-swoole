<?php

namespace frame\log;

use Psr\Log\AbstractLogger;

class BacktraceDecorator implements Decorator {

    private $skipClasses = [Logger::class, Log::class, AbstractLogger::class];

    public function decorate(array &$message) {
        $trace = debug_backtrace();
        $index = -1;
        for ($i = 0; $i < count($trace); $i++) {
            if ($this->skipFrame($trace[$i])) {
                $index = $i;
                break;
            }
        }

        for ($i = $index; $i < count($trace); $i++) {
            if (!$this->skipFrame($trace[$i])) {
                $index = $i;
                break;
            }
        }

        $value = '';
        if ($index > 0) {
            $value .=  $trace[$index]['class'] . ' -> ' . $trace[$index]['function'];;
            if (isset($trace[$index - 1])) {
                $names = explode(DIRECTORY_SEPARATOR, $trace[$index - 1]['file']);
                $file = array_pop($names);
                $value = '(' . $file . ':' . $trace[$index - 1]['line'] .') ' . $value;
            }
        }
        $message['trace'] = $value;
    }

    private function skipFrame($frame) {
        return in_array($frame['class'], $this -> skipClasses);
    }

} 