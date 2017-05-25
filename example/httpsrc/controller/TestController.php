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

    private function actionTest() {
        $this->send('Hello World');
    }
    
    public function actionDbtest() {
        Log::info("action db test");
        $model = new TestModel();
        $data = $model->dbTest();
        $this ->send(print_r($data, true));
    }

}
