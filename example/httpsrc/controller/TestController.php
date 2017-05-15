<?php

use frame\log\Log;

class TestController extends \frame\base\Controller
{

    public function actionTest()
    {
		Log::info("action test");
        $Totaldata = $this->getRequest();
        /*
            uds 测试 qidian的UDS配置， 查询操作
         */
        $rsp = (yield $this ->udsTest());

        $this ->send(' HELLO WORLD ');
    }

    private function udsTest(){

        $model = new TestModel();
        $rsp = (yield $model ->udsTest());
        Log::info(__METHOD__ . " rsp == " . print_r($rsp, true));
        yield $rsp;
    }

}
