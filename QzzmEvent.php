<?php

use qzzm\mysql\Config;
use qzzm\mysql\DbPool;
use qzzm\lib\Config as rConfig;

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
        $val = rConfig::getInstance()->get('MYSQL.MYSQL1.host');
        var_dump($val);

        DbPool::getInstance()->addConnection(
            Config::getInstance()
                ->setHost('127.0.0.1')
                ->setDbName('erp')
                ->setUserName('root')
                ->setPassword('root')
                ->setPrefix('qzzm_')
                ->setCharset('utf8mb4'),
            'mysql'
        );

        DbPool::getInstance()->addConnection(
            Config::getInstance()
                ->setHost('127.0.0.1')
                ->setDbName('erp1')
                ->setUserName('root')
                ->setPassword('root')
                ->setPrefix('qzzm_')
                ->setCharset('utf8mb4'),
            'mysql1'
        );
    }

    /**
     * 接收请求勾子
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return bool
     */
    static function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
//        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, XMLHttpRequestUpload, Qzzm-Token, Qzzm-Domain, Qzzm-Action');

        if ($request->server['request_method'] === 'OPTIONS') {
            $response->status(200);
            return false;
        }
        return true;
    }
}