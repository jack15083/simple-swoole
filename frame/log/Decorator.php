<?php


namespace frame\log;


interface Decorator {

    public function decorate(array &$message);

} 