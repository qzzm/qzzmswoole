<?php


namespace qzzm\server;


interface IWebSocketEvent
{
    /**
     * 连接成功事件
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request $request
     * @return mixed
     */
    static function open(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request);

    /**
     * 断开连接事件
     * @param \Swoole\WebSocket\Server $server
     * @param int $fd
     * @return mixed
     */
    static function close(\Swoole\WebSocket\Server $server, int $fd);
}