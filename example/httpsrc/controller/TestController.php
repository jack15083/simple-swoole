<?php

use frame\log\Log;

class TestController extends \frame\base\Controller
{

    public function actionHttptest()
    {
		Log::info("action http test");
        $model = new TestModel();
        $data = $model->httpTest();

        $this ->send(print_r($data, true));
    }

    public function actionTest() {
        $this->send('Hello World');
    }
    
    public function actionDbtest() {
        Log::info("action db test");
        $model = new TestModel();
        $data = $model->dbTest();
        $this->header("Content-Type", "text/html; charset=utf-8");
        $this ->send(print_r($data, true));
    }
    
    public function actionTestPool() {
        Log::info("action db test");
        $model = new TestModel();
        $data = $model->mysqliTest();
        $this->header("Content-Type", "text/html; charset=utf-8");
        $this ->send(print_r($data, true));
    }

}
