<?php
define('APP_PATH', __DIR__);
require_once APP_PATH . '/config/envcnf/ol/ENVConst.php';
require_once dirname(APP_PATH) . '/weblib/require.php';

//load app Config
$appConfig = require(APP_PATH . '/config/UserConfig.php');

//init app router
$router = new \frame\base\Router();
$router = $router->loadRoute(APP_PATH . '/route/');

//require_once '../weblib/require.php';
$app = \frame\App::createApplication($appConfig, $router); //返回

return $app;