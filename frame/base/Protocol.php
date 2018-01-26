<?php
/**
 * Protocol base class
 * @author zengfanwei
 */
namespace frame\base;

class Protocol
{
    public $router;

    public function __construct(Router $router) {
        $this->router = $router;
    }

    public function onReceive($server, $clientId, $fromId, $data)
    {

    }

    public function onStart($serv, $workerId)
    {
    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {

    }

    public function onClose($server, $fd, $fromId)
    {

    }

    public function onRequest($request, $response)
    {

    }


    public function onRoute($request)
    {

    }
}