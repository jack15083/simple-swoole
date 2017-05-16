<?php 
namespace frame\base;

use frame\base\Route;

class RouteRegex extends \frame\base\Protocol
{


    public function onRoute($req)  //默认为
    {
        $uri  = $req->data['server']['request_uri'];
        $verb = $req->data['server']['request_method'];

        //读取配置文件
        $rewrite = $this->restRule();
        if (empty($rewrite) or !is_array($rewrite)) 
        {
            return false;
        }
        
        $match = array();
        $uri_for_regx = $uri;
        
        foreach ($rewrite['Rewrite'] as $rule) 
        {
            //如果设置了规则，并且传进来的不同 则pass 如果未设置，则不需要考虑
            if ((!empty($rule['verb'])) && ($verb != $rule['verb'])) 
            {
                continue;
            }
            $mvc = $rule['mvc'];
            $mvcArr = explode('/', $mvc);
            if (count($mvcArr) < 2) 
            {  //如果小于2 则返回false
                return false;
            }
            
            $mvc = array();
            $mvc['controller'] = $mvcArr[0];  //获取了controller 和 action
            $mvc['action'] = $mvcArr[1];
            $tmp = array();
            if (preg_match_all('/<\w+>/', $rule['regx'], $match)) 
            {
                foreach ($match[0] as $k => $v) {  //赋值到get参数内 按照顺序筛选出来 赋值出来key值
                    $tmp[] = trim($v, '<>');
                }
            };
            $regx = preg_replace('/<\w+>/', '', $rule['regx']); //获得实际的正则表达式
            if (preg_match('#' . $regx . '#i', $uri_for_regx, $match)) {
                //如果设置了mvc 则走指定的controller
                foreach ($tmp as $k => $v) {
                    if ($v == 'controller') {
                        $mvc['controller'] = ucwords($match[$k + 1]);  //获取了controller 和 action
                        continue;
                    }
                    if ($v == 'action') {
                        $mvc['action'] = ucwords($match[$k + 1]);
                        continue;
                    }

                    if ($v == 's_action') {
                        $mvc['action'] .= ucwords($match[$k + 1]);
                    }
                    //如果不是controller 也不是 action 则放入get参数中
                    $tmpGet[$v] = $match[$k + 1];
                    // $_GET[$v] = $match[$k + 1];
                };
                //强制转为
                if (isset($tmpGet)) {     //如果设置了
                    $mvc['get'] = array_merge($rule['default'], (array)$tmpGet); //以tmpGet去覆盖default
                } else {
                    $mvc['get'] = $rule['default'];
                }
                return new Route($mvc['controller'] . 'Controller', 'action' . $mvc['action'], $mvc['get']);
            }
        }
        return false;
    }


    public function restRule()
    {
        return array(
            'Rewrite' => array(
                //特殊
                array(
                    'regx' => '^/(<controller>\w+)/(<action>\w+)/(<s_action>\w+)$',
                    'mvc' => 'controller/Index',
                    'verb' => '',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),

                //添加rest
                array(
                    'regx' => '^/rest/(<controller>\w+)$',
                    'mvc' => 'Controller/List',
                    'verb' => 'GET',
                    'default' => array(),
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
                    'mvc' => 'Controller/View',
                    'verb' => 'GET',
                    'default' => array(),
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
                    'mvc' => 'Controller/Update',
                    'verb' => 'PUT',
                    'default' => array(),
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)$',
                    'mvc' => 'Controller/View',
                    'verb' => 'GET',
                    'default' => array(),
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)$',
                    'mvc' => 'Controller/Update',  //必须匹配
                    'verb' => 'PUT',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)$',
                    'mvc' => 'Controller/Create',  //必须匹配
                    'verb' => 'POST',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)/(<id>\d+)$',
                    'mvc' => 'Controller/Delete',  //必须匹配
                    'verb' => 'DELETE',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/rest/(<controller>\w+)$',
                    'mvc' => 'Controller/Delete',  //必须匹配
                    'verb' => 'DELETE',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/(<controller>\w+)/(<action>\w+)/(<cid>\d+)/(<name>\w+)$',
                    'mvc' => 'Controller/Action',  //必须匹配
                    'verb' => 'GET',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/(<controller>\w+)$',  //默认到index
                    'mvc' => 'controller/Index',  //必须匹配
                    'verb' => '',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
                array(
                    'regx' => '^/(<controller>\w+)/(<action>\w+)$',
                    'mvc' => 'controller/Index',
                    'verb' => '',  //必须匹配 方法
                    'default' => array(),  //添加默认参数
                ),
            )
        );
    }

}