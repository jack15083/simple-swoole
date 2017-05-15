<?php


namespace frame\log;


class ConsoleAppender extends Appender {

    public function append(array $message){
        echo $this -> formatter->format($message);
        return true;
    }

} 