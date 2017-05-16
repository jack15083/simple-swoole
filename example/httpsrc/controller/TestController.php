<?php

use frame\log\Log;

class TestController extends \frame\base\Controller
{

    public function actionTest()
    {
		Log::info("action test");
        $Totaldata = $this->getRequest();
        $rsp = $this->httpTest();

        $this ->send(' HELLO WORLD ');
    }

    private function httpTest(){

        $model = new TestModel();
        $rsp = $model->httpTest();
        Log::info(__METHOD__ . " rsp == " . print_r($rsp, true));
        return $rsp;
    }

}
