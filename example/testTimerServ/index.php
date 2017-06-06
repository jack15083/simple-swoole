<?php

//$config = dirname(__FILE__) . '/config/UserConfig.php'; //加载用户的config 一些非标准化的配置文件，需要在这边加载 其实可以不需要只是代入路径
//加载环境变量    通过 $_SERVER['NEWAPI_ENV']来加载不同的配置文件 require_once dirname(__FILE__) . '/config/envcnf/'. $_SERVER['SERV_ENV'] .'/ENVConst.php';
//require_once dirname(__FILE__) . '/config/' . $_SERVER['SERV_ENV'] . '/ENVConst.php';

//define('APP_PATH', dirname(__FILE__));
return \frame\App::createApplication(); //返回
