<?php
/**
 * Main App class
 * @author zengfanwei
 */
namespace frame;

use frame\base\Router;
use frame\base\UserConfig;
use frame\log\Log;
use frame\core\Event;
use frame\base\RouteRegex;

class App
{
    /**
     * @param array $config
     * @param Router $router
     * @return bool|core\Event
     */
    public static function createApplication($config = array(), Router $router) {
        //初始化log组件
        $userConfig = new UserConfig();
        if(!empty($config)) $userConfig->setConfig($config);
        Log::create($userConfig->getConfig('log'));

        $Sever    = new Event();
        $register = $userConfig->getConfig('register');

        //是否存在自定义的路由协议
        if (isset($register['protocol']) && class_exists($register['protocol'])) {
            $protocol = new $register['protocol']($router);
            if (is_subclass_of($protocol, 'frame\base\Protocol')) {
                $Sever->setRegister('protocol', $protocol);
            } else {
                return false;
            }
        } else {//使用默认的路由函数
            $protocol = new RouteRegex($router);
            $Sever->setRegister('protocol', $protocol);
        }

        return $Sever;
    }

}