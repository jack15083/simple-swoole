<?php
use \frame\log\Log;

class TestTask extends \frame\core\Task {
	public function __construct($data) {
		$this->data = $data;
	}

	public function onTask() {
		Log::info(__LINE__.$this->data);
		$this->data = "GoodBye";
	}

	public function onFinish() {
		Log::info(__LINE__.$this->data);
	}
}