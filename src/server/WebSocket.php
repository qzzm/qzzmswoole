<?php


namespace qzzm\server;

require_once QZZM_ROOT . '/QzzmEvent.php';

use qzzm\lib\Singleton;
use qzzm\lib\Str;
use qzzm\lib\System;

class WebSocket
{
    use Singleton;


    function run(array $args)
    {
        $config = require QZZM_ROOT . '/config.php';
        $listenAddress = $config['SERVER']['LISTEN_ADDRESS'] ?? '127.0.0.1';
        $listenPort = $config['SERVER']['LISTEN_PORT'] ?? 10080;
        $isHttp = $config['SERVER']['IS_HTTP'] ?? 0;
        $mode = $config['MODE'] ?? 0;
        $reactorNum = $config['SERVER']['SETTING']['reactor_num'] ?? null;
        $workerNum = $config['SERVER']['SETTING']['worker_num'] ?? null;
        $cacheType = $config['CACHE_TYPE'] ?? [];

        if (in_array(1, $cacheType)) {
            \qzzm\cache\RAM::getInstance()->create();
        }

        $commandParam = array_shift($args);
        $server = new \Swoole\WebSocket\Server($listenAddress, $listenPort);
        $server->set([
            'enable_coroutine' => true,
            'reactor_num' => $reactorNum,
            'worker_num' => $workerNum,
            'log_level' => SWOOLE_LOG_ERROR, //日志级别
            'daemonize' => $commandParam === 'd' && $mode === 1 ? 1 : 0
        ]);
        $server->on('start', function (\Swoole\Server $server) use ($listenAddress, $listenPort, $mode, $isHttp) {
            //保存进程id
            $dir = QZZM_ROOT . '/temp';
            $qzzmFile = $dir . '/qzzm.pid';
            System::mk_dir($dir);
            file_put_contents($qzzmFile, $server->master_pid);

            \QzzmEvent::boot($server);

            $phpVersion = phpversion();
            $swooleVersion = swoole_version();
            $showInfo = "Welcome to using Qzzm!\n";
            $showInfo .= "php\t\t{$phpVersion}\n";
            $showInfo .= "swoole\t\t{$swooleVersion}\n";
            $showInfo .= "pid\t\t{$server->master_pid}\n";
            $showInfo .= "websocket\tws://{$listenAddress}:{$listenPort}\n";
            if ($isHttp) {
                $showInfo .= "http\t\thttp://{$listenAddress}:{$listenPort}\n";
            }
            $showInfo .= "`Ctrl+C` can shutdown this server.\n";
            echo $showInfo;

            if ($mode === 0) {
                //开发模式 监听文件变化 热重启
                HotReload::getInstance()->run(['server' => $server]);
            }
        });

        $server->on('workerStart', function (\Swoole\WebSocket\Server $server, int $workerId) {
//            echo "workerStart事件\n";
            \QzzmEvent::init($workerId, $server);
        });

        $server->on('open', function (\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request) {
            $class = "\\app\\websocket\\Event";
            if (class_exists($class)) {
                if (is_callable([$class, 'open'])) {
                    \app\websocket\Event::open($server, $request);
                }
            }
        });


        $server->on('message', function (\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) {
//            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            try {
                if (strtoupper($frame->data) === 'PING') {
                    $server->push($frame->fd, 'PING');
                } else {
                    $requestData = json_decode($frame->data, true);
                    if (isset($requestData['url'])) {
                        $pathArr = explode('/', $requestData['url']);
                        $module = $pathArr[1] ?? '';
                        $controller = $pathArr[2] ?? '';
                        $function = $pathArr[3] ?? '';
                        $dir = "\\app\\websocket\\controller\\{$module}\\" . ucfirst(Str::convertUnderline($controller));

                        if (class_exists($dir)) {
                            $header = ['path' => $requestData['url'], 'token' => $requestData['token'] ?? null];
                            $toFd = $requestData['toFd'] ?? 0;
                            $class = new $dir($server, $frame->fd, $requestData['data'] ?? [], $header, $toFd);
                            if (is_callable([$class, $function])) {
                                $class->$function();
                            }
                        }
                    } else {
                        $server->push($frame->fd, "error data");
                    }
                }
            } catch (\Exception $ex) {
                $server->push($frame->fd, $ex->getMessage());
            }
//            $server->push($frame->fd, "this is server");
            //{"url":"/erp/demand/index","token":"myToken","data":{"id":12}}
        });

        if ($isHttp === 1) {
            $server->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) use ($server) {
//                var_dump($request->server['remote_addr']);
//            $response->end("websocket's http");
                // 接收http请求从get获取message参数的值，给用户推送
                // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
//            foreach ($server->connections as $fd) {
//                // 需要先判断是否是正确的websocket连接，否则有可能会push失败
//                if ($server->isEstablished($fd)) {
//                    $server->push($fd, $request->get['message']);
//                }
//            }
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
//                        $module = $pathArr[1] ?? '';
//                        $controller = $pathArr[2] ?? '';
//                        $function = $pathArr[3] ?? '';
//                        $dir = "\\app\\http\\controller\\{$module}\\" . ucfirst(Str::convertUnderline($controller));

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
        }

        $server->on('close', function (\Swoole\Server $server, int $fd) {
            $class = "\\app\\websocket\\Event";
            if (class_exists($class)) {
                if (is_callable([$class, 'close'])) {
                    \app\websocket\Event::close($server, $fd);
                }
            }
        });
        $server->start();
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