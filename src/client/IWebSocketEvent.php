<?php


namespace qzzm\client;


interface IWebSocketEvent
{
    /**
     * 连接成功事件
     * @param \Swoole\Coroutine\Http\Client $client
     * @return mixed
     */
    static function open(\Swoole\Coroutine\Http\Client $client): bool;
}