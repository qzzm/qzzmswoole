<?php


namespace qzzm\server;


abstract class WebSocketController
{
    protected $server;
    protected $fd;
    protected $data;
    protected $header;
    protected $toFd;

    function __construct(\Swoole\WebSocket\Server $server, int $fd, array $data, array $header, int $toFd = 0)
    {
        $this->server = $server;
        $this->fd = $fd;
        $this->data = $data;
        $this->header = $header;
        $this->toFd = $toFd;
    }
}