<?php

namespace frame\base;

class RouteRegex extends Protocol
{
    /**
     * Match the request uri
     * @param $req
     * @return bool|Route
     */
    public function onRoute($req)
    {
        $uri    = $req->data['server']['request_uri'];
        $method = $req->data['server']['request_method'];
        $path   = '/' . trim($uri, '/');

        $routes = $this->router->getRoutes();
        if (isset($routes[$method . $path])) {
            $route  = $routes[$method . $path];
        } else {
            $route = $this->getRouteRegx($method, $path, $routes);
        }

        if(empty($route)) return false;

        $action = $route['action'];
        $arr = explode('@', $action);
        return new Route($arr[0], $arr[1], $route['get']);
    }

    /**
     * get route by regex
     * @param $method
     * @param $path
     * @param $routes
     * @return mixed
     */
    protected function getRouteRegx($method, $path, $routes)
    {
        $pathArr = explode('/', $path);

        foreach ($routes as $uri => $row)
        {
            preg_match_all('/\{(\w+)\}/', $uri, $matches);

            if (empty($matches[1]))
                continue;

            $matchCount = count($matches[1]);

            if (count($pathArr) < $matchCount)
                continue;

            for ($i = 0 ; $i < $matchCount; $i++) {
                $getVal[$matchCount - $i] = array_pop($pathArr);
            }

            $pathReg = '#^' . $method . implode('/', $pathArr);
            foreach ($matches[1] as $key => $name) {
                $pathReg .= '/\{(\w+)\}';
            }
            $pathReg .= '$#';

            if(!preg_match($pathReg, $uri, $matchesKey)) {
                return false;
            }

            foreach ($matchesKey as $key => $item) {
                if ($key == 0)  continue;
                $get[$item] = isset($getVal[$key]) ? $getVal[$key] : '';
            }

            $row['get'] = $get;

            return $row;
        }

        return false;
    }

}