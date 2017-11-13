<?php

$router->get('/test', 'TestController@actionTest');
$router->get('/test1', 'TestController@actionTest');
$router->get('/test.html', 'TestController@actionTest');
$router->get('/test/{username}/{id2}/{id3}', 'TestController@actionTest');