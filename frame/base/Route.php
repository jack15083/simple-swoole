<?php
/**
 * Base route class
 * @author zengfanei
 */
namespace frame\base;


class Route
{
    public $class;
    public $action;
    public $data;

    public function __construct($class, $action, $data)
    {
        $this->class = $class;
        $this->action = $action;
        $this->data = $data;
    }
}
