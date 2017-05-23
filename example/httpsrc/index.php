<?php
require_once dirname(__FILE__) . '/config/envcnf/ol/ENVConst.php';
$db = new \frame\client\mysql(ENVConst::getDBConf());
return \frame\App::createApplication(); //返回
