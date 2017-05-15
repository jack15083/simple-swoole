<?php
/**
 * @Author: winterswang
 * @Date:   2015-08-18 11:20:10
 * @Last Modified by:   winterswang
 * @Last Modified time: 2015-08-18 19:58:39
 */

namespace tsf\core;
class Table {

	protected $size;
	protected $table;
	protected $columns = array();

	const STRING = \swoole_table::TYPE_STRING;
	const INT = \swoole_table::TYPE_INT;
	const FLOAT = \swoole_table::TYPE_FLOAT;

	public function __construct($size = 4, $columns = array()){

		$this ->size = $size;
		$this ->columns = $columns;
		$this ->table = new \swoole_table($this ->size);

		if (!empty($this ->columns)) {
			$this ->init();
		}	
	}

	public function addIntColumn($key, $size = 4){

		$this ->columns[]['key'] = $key;
		$this ->columns[]['type'] = self::INT;
		$this ->columns[]['size'] = $size;

		$this ->table ->column($key, self::INT, $size);
	}

	public function addStringColumn($key, $size = 64){

		$this ->columns[]['key'] = $key;
		$this ->columns[]['type'] = self::STRING;
		$this ->columns[]['size'] = $size;

		$this ->table ->column($key, self::STRING, $size);
	}

	public function addFloatColumn($key){

		$this ->columns[]['key'] = $key;
		$this ->columns[]['type'] = self::FLOAT;

		$this ->table ->column($key, self::FLOAT);
	}

	private function init(){

		foreach ($this ->columns as $key => $clo) {
			if (isset($clo['size'])) {

				$this ->table ->column($clo['key'], $clo['type'], $clo['size']);
			}
			else{

				$this ->table ->column($clo['key'], $clo['type']);
			}
		}

		return $this ->table ->create();
	}

	public function setColData($key, $colData = array()){

		return $this ->table ->set($key, $colData);
	}

	public function incr($key, $clo, $incrby){

		return $this ->table ->incr($key, $clo, $incrby);
	}

	public function decr($key, $col, $decrby){

		return $this ->table ->decr($key, $col, $decrby);
	}

	public function getColData($key){

		return $this ->table ->get($key);
	}
}