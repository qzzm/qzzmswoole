<?php


namespace qzzm\client;


abstract class WebSocketController
{
    protected $client;
    protected $data;
    protected $fromFd;
    protected $callbackUrl;

    function __construct(\Swoole\Coroutine\Http\Client $client, array $data, int $fromFd = 0, ?string $callbackUrl)
    {
        $this->client = $client;
        $this->data = $data;
        $this->fromFd = $fromFd;
        $this->callbackUrl = $callbackUrl;
    }
}