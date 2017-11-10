<?php

namespace frame\base;

use frame\log\Log;

class RouteRegex extends Protocol
{

    public function onRoute($req) {

        $uri    = strtolower($req->data['server']['request_uri']);
        $method = $req->data['server']['request_method'];
        $path   = '/' . trim($uri, '/');

        Log::debug('--------start debug route---------');
        Log::debug($uri);
        Log::debug($method);
        Log::debug($path);

        $routes = $this->router->getRoutes();

        Log::debug(print_r($routes, true));

        if(!isset($routes[$method . $path]))
            return false;

        $route  = $routes[$method . $path];

        $action = $route['action'];
        $arr = explode('@', $action);

        return new Route($arr[0], $arr[1], []);
    }

}