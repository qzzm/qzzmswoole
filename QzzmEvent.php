<?php

use qzzm\factory\Register;

final class QzzmEvent
{
    /**
     * 启动事件
     * @param \Swoole\Http\Server $server
     */
    static function boot(Swoole\Http\Server $server)
    {
        //设置时区
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * 初始化服务事件
     * @param int $pid
     * @param \Swoole\Http\Server $server
     */
    static function init(int $pid, Swoole\Http\Server $server)
    {
        Register::getInstance()->mysql();
    }

    /**
     * 接收请求勾子
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return bool
     */
    static function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
        Register::getInstance()->cross($request, $response);

        if ($request->server['request_method'] === 'OPTIONS') {
            $response->status(200);
            return false;
        }
        return true;
    }
}