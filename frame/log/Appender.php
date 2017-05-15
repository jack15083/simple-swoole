<?php

namespace frame\log;

abstract class Appender {

    protected $formatter;

    public function __construct(Formatter $formatter = null) {
        $this->formatter = $formatter ?: new LineFormatter();
    }

    public abstract function append(array $message);

}