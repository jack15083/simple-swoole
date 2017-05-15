<?php


namespace frame\log;


class LineFormatter implements Formatter {

    public function format(array $message) {
        $text = $message['__text'];
        unset($message['__text']);
        $log = '';
        foreach($message as $value) {
            $log .= "[$value] ";
        }
        return $log . "- $text" . PHP_EOL;
    }

}