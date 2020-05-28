<?php

namespace qzzm\command;

use qzzm\lib\Singleton;
use qzzm\server\Http;
use qzzm\server\WebSocket;

//use qzzm\server\Https;


class Runner
{
    use Singleton;

    function run(array $args): ?string
    {
        $config = require QZZM_ROOT . '/config.php';
        $isWebSocket = $config['SERVER']['IS_WEBSOCKET'] ?? 0;
        $isHttp = $config['SERVER']['IS_HTTP'] ?? 0;
        $isSSL = $config['SERVER']['IS_SSL'] ?? 0;

        $command = array_shift($args);
        switch ($command) {
            case 'start':
                if ($isSSL === 0) {
                    if ($isWebSocket === 1) {
                        WebSocket::getInstance()->run($args);
                        return null;
                    }
                    if ($isWebSocket === 0 && $isHttp === 1) {
                        Http::getInstance()->run($args);
                        return null;
                    }
                } else {
                    //ssl服务
                }
                break;
            case 'stop':
                Http::getInstance()->stop();
                break;
            case 'client/start':
                \qzzm\client\WebSocket::getInstance()->run($args);
                break;
            case 'client/stop':
                \qzzm\client\WebSocket::getInstance()->stop($args);
                break;
        }
        return null;
    }
}