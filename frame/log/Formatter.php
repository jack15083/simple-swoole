<?php

namespace frame\log;


interface Formatter {

    public function format(array $message);

}