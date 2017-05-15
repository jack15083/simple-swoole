<?php

namespace frame\base;

class Protocol
{
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