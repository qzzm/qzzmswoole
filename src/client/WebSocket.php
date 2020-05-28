<?php

namespace qzzm\client;

require_once QZZM_ROOT . '/QzzmClientEvent.php';

use qzzm\lib\Singleton;
use qzzm\lib\Str;
use qzzm\lib\System;

class WebSocket
{
    use Singleton;

    function run(array $args)
    {
        $config = require QZZM_ROOT . '/config.php';
        $listenAddress = $config['CLIENT']['LISTEN_ADDRESS'] ?? '127.0.0.1';
        $listenPort = $config['CLIENT']['LISTEN_PORT'] ?? 10080;
        $heartCheckTime = $config['CLIENT']['HEART_CHECK_TIME'] ?? 60;
        $token = $config['CLIENT']['TOKEN'] ?? '';
        $workerNum = $config['CLIENT']['SETTING']['worker_num'] ?? 1;
        $mode = $config['MODE'] ?? 0;
        $cacheType = $config['CACHE_TYPE'] ?? [];

        $commandParam = array_shift($args);
        if ($commandParam === 'd' && $mode === 1) {
            // 守护进程
            echo "守护进程已启动\n";
            \Swoole\Process::daemon();
        }

        \Swoole\Runtime::enableCoroutine();
        $pool = new \Swoole\Process\Pool($workerNum);
        $pool->set(['enable_coroutine' => true]);
        $pool->on("workerStart", function (\Swoole\Process\Pool $pool, int $workerId) use ($listenAddress, $listenPort, $token, $mode, $cacheType, $heartCheckTime) {

            if ($workerId === 0) {
                //保存进程id
                $dir = QZZM_ROOT . '/temp';
                $qzzmFile = $dir . '/qzzm_client.pid';
                System::mk_dir($dir);
                file_put_contents($qzzmFile, $pool->master_pid);

                $phpVersion = phpversion();
                $swooleVersion = swoole_version();
                $showInfo = "Welcome to using Qzzm!\n";
                $showInfo .= "php\t\t{$phpVersion}\n";
                $showInfo .= "swoole\t\t{$swooleVersion}\n";
                $showInfo .= "pid\t\t{$pool->master_pid}\n";
                $showInfo .= "remote \t{$listenAddress}:{$listenPort}\n";
                $showInfo .= "`Ctrl+C` can shutdown this client.\n";
                echo $showInfo;
                if ($mode === 0) {
                    //开发模式 监听文件变化 热重启
                    $pid = $pool->getProcess()->pid;
                    HotReload::getInstance()->run(['pid' => $pid]);
                }
            }

            if (in_array(1, $cacheType)) {
                \qzzm\cache\RAM::getInstance()->create();
            }

            \QzzmClientEvent::init();
            $i = 1;
            while (true) {
                $client = WebSocketClient::getInstance()->create($listenAddress, $listenPort, $token, $heartCheckTime);
                $client->run();
                echo "正在执行第 {$i} 次重连服务器" . "\n";
                for ($j = 0; $j < 50; $j++) {
                    echo ".";
                    \co::sleep(0.1);
                }
                echo "\n";
                $i++;
                if ($client->getClient()->getStatusCode() === -3) {
                    $i = 1;
                }
                \co::sleep(0.01);
            }
        });
        $pool->start();
    }


    function stop()
    {
        $qzzmFile = QZZM_ROOT . '/temp/qzzm_client.pid';
        $pid = intval(file_get_contents($qzzmFile));
        if ($pid > 0) {
            if (!\Swoole\Process::kill($pid, 0)) {
                return "PID :{$pid} not exist ";
            } else {
                \Swoole\Process::kill($pid, SIGTERM);
                echo "Thank you for used Qzzm! \n";
                file_put_contents($qzzmFile, '');
            }
        }
    }
}