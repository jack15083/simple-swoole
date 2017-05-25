<?php

use frame\log\Log;

class TestController extends \frame\base\Controller
{

    public function actionHttptest()
    {
		Log::info("action http test");
        $Totaldata = $this->getRequest();
        $rsp = $this->httpTest();

        $this ->send(' HELLO WORLD ' . print_r($rsp, true));
    }

    private function httpTest() {
        $model = new TestModel();
        $rsp = $model->httpTest();
        return $rsp;
    }
    
    public function actionDbtest() {
        Log::info("action db test");
        $model = new TestModel();
        $data = $model->dbTest();
        $this ->send(print_r($data, true));
    }

}
