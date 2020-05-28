<?php


namespace qzzm\server;

require_once QZZM_ROOT . '/QzzmEvent.php';

use qzzm\lib\Singleton;
use qzzm\lib\System;
use qzzm\lib\Str;

class Http
{
    use Singleton;

    function run(array $args)
    {
        $config = require QZZM_ROOT . '/config.php';
        $listenAddress = $config['SERVER']['LISTEN_ADDRESS'] ?? '127.0.0.1';
        $listenPort = $config['SERVER']['LISTEN_PORT'] ?? 10080;
        $mode = $config['MODE'] ?? 0;
        $reactorNum = $config['SERVER']['SETTING']['reactor_num'] ?? null;
        $workerNum = $config['SERVER']['SETTING']['worker_num'] ?? null;
        $cacheType = $config['CACHE_TYPE'] ?? [];

        if (in_array(1, $cacheType)) {
            \qzzm\cache\RAM::getInstance()->create();
        }

        $commandParam = array_shift($args);
        $http = new \Swoole\Http\Server($listenAddress, $listenPort);
        $http->set([
            'enable_coroutine' => true,
            'reactor_num' => $reactorNum,
            'worker_num' => $workerNum,
            'log_level' => SWOOLE_LOG_ERROR, //日志级别
            'daemonize' => $commandParam === 'd' && $mode === 1 ? 1 : 0
        ]);
        $http->on('start', function (\Swoole\Server $server) use ($listenAddress, $listenPort, $mode) {
            //保存进程id
            $dir = QZZM_ROOT . '/temp';
            $qzzmFile = $dir . '/qzzm.pid';
            System::mk_dir($dir);
            file_put_contents($qzzmFile, $server->master_pid);

            \QzzmEvent::boot($server);

            $phpVersion = phpversion();
            $swooleVersion = swoole_version();
            $showInfo = "Welcome to using Qzzm!\n";
            $showInfo .= "php\t{$phpVersion}\n";
            $showInfo .= "swoole\t{$swooleVersion}\n";
            $showInfo .= "pid\t{$server->master_pid}\n";
            $showInfo .= "host\thttp://{$listenAddress}:{$listenPort}\n";
            $showInfo .= "`Ctrl+C` can shutdown this server.\n";
            echo $showInfo;

            if ($mode === 0) {
                //开发模式 监听文件变化 热重启
                HotReload::getInstance()->run(['server' => $server]);
            }
        });

        $http->on('workerStart', function (\Swoole\Server $server, int $workerId) {
            \QzzmEvent::init($workerId, $server);
        });

        $http->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                $response->status(404);
                $response->end();
                return;
            }
            if (!\QzzmEvent::onRequest($request, $response)) {
                return;
            }
            $path = $request->server['request_uri'];
            if ($path !== '/') {
                try {
                    $pathArr = explode('/', $request->server['request_uri']);
//                    $module = $pathArr[1] ?? '';
//                    $controller = $pathArr[2] ?? '';
//                    $function = $pathArr[3] ?? '';
//                    $dir = "\\app\\http\\controller\\{$module}\\" . ucfirst(Str::convertUnderline($controller));

                    $count = count($pathArr);
                    $pathClass = '';
                    for ($i = 0; $i < $count - 1; $i++) {
                        if ($i > 0) {
                            $pathItem = $pathArr[$i];
                            if ($i === ($count - 2)) {
                                $pathItem = ucfirst(Str::convertUnderline($pathItem));
                            }
                            $pathClass .= "\\{$pathItem}";
                        }
                    }
                    $dir = "\\app\\http\\controller" . $pathClass;
                    $function = $pathArr[$count - 1];

                    if (class_exists($dir)) {
                        $class = new $dir($request, $response);
                        if (is_callable([$class, $function])) {
                            $res = $class->$function();
                            if (gettype($res) === 'array') {
                                $response->write(json_encode($res));
                                $response->end();
                            }
                        } else {
                            $response->status(404);
                            $response->end();
                            return;
                        }
                    } else {
                        $response->status(404);
                        $response->end();
                        return;
//                        echo "class `{$dir}` is not exist\n";
                    }
                } catch (\Exception $ex) {
//                    throw $ex;

                }
            } else {
                $response->status(404);
                $response->end();
                return;
                //执行默认首页的操作
//                    var_dump('访问默认首页,api接口不做任何事情');
            }
        });

        $http->on('close', function (\Swoole\Server $server, int $fd, int $reactorId) {
//            echo $fd . ' - ' . $reactorId . "\n";
        });
        $http->start();
    }

    function stop()
    {
        $qzzmFile = QZZM_ROOT . '/temp/qzzm.pid';
        $pid = intval(file_get_contents($qzzmFile));
        if ($pid > 0) {
            if (!\Swoole\Process::kill($pid)) {
                return "PID :{$pid} not exist ";
            } else {
                echo "Thank you for used Qzzm! \n";
                file_put_contents($qzzmFile, '');
            }
        }
    }
}