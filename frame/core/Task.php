<?php
namespace frame\core;

class Task
{
    private $data;

    public function __construct($data = '')
    {
        $this->data = $data;
    }

    public function onTask()
    {
    }

    public function onFinish()
    {
    }
}