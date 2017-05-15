<?php
namespace frame\pool;

class Mysqli extends Resource {

    public $host;
    public $username;
    public $passwd;
    public $dbname;
    public $timeout;

    /**
     * [__construct description]
     * @param [type]  $host     [description]
     * @param [type]  $username [description]
     * @param [type]  $passwd   [description]
     * @param [type]  $dbname   [description]
     * @param integer $timeout  [description]
     */
    public function __construct($host, $username, $passwd, $dbname, $timeout = 5){

        $this ->host = $host;
        $this ->username = $username;
        $this ->passwd = $passwd;
        $this ->dbname = $dbname;
        $this ->timeout = $timeout;

    }

    /**
     * [apply 申请资源]
     * @param  callable $callback [description]
     * @param  array    $argv     [description]
     * @return [type]             [description]
     */
    public function apply(callable $callback, $argv = array()){

        return MysqlPool::getResource($callback, get_object_vars($this));
    }

}