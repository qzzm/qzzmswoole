<?php


namespace qzzm\client;

use qzzm\utlis\Singleton;
use qzzm\utlis\Str;

class WebSocketClient
{
    use Singleton;

    private $client;
    private $ret;
    private $heartCheckTime;

    function create(string $listenAddress, int $listenPort, string $token, int $heartCheckTime = 60): WebSocketClient
    {
        $this->heartCheckTime = $heartCheckTime;
        $this->client = new \Swoole\Coroutine\Http\Client($listenAddress, $listenPort);
//        $this->client->set([
////            'http_proxy_host' => '27.38.98.203',
////            'http_proxy_port' => 9797,
//            'http_proxy_host' => '118.25.10.200',
//            'http_proxy_port' => 8080,
//        ]);
        $this->ret = $this->client->upgrade("/?token={$token}&platform=client");
        return $this;
    }

    public function getClient(): \Swoole\Coroutine\Http\Client
    {
        return $this->client;
    }

    public function run()
    {
        $client = $this->getClient();

        go(function () use ($client) {
            $class = "\\app\\client\\Event";
            if (class_exists($class)) {
                if (is_callable([$class, 'open'])) {
                    while (true) {
                        if (\app\client\Event::open($client)) {
                            break;
                        }
                        \co::sleep(0.001);
                    }
                }
            }
        });

        if ($this->ret) {
            go(function () use ($client) {
                $this->heartCheck($client, $this->heartCheckTime);
            });
            while (true) {
                $recv = $client->recv();
                if (!$recv) {
                    break;
                }
                go(function () use ($recv, $client) {
                    $requestData = json_decode($recv->data, true);
                    if (isset($requestData['url'])) {
                        $pathArr = explode('/', $requestData['url']);
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
                        $dir = "\\app\\client\\controller" . $pathClass;
                        $function = $pathArr[$count - 1];

                        if (class_exists($dir)) {
                            $class = new $dir($client, $requestData['data'] ?? [], $requestData['fromFd'] ?? 0, $requestData['callbackUrl'] ?? null);
                            if (is_callable([$class, $function])) {
                                $class->$function();
                            } else {
                                echo "`{$dir}\\{$function}` is a private method, can not be called.\n";
                            }
                        } else {
                            echo "`{$dir}` class is not exist.\n";
                        }
                    }
                });
            }
        }
        $client->close();
    }

    private function heartCheck(\Swoole\Coroutine\Http\Client $client, int $second)
    {
        while (true) {
            if ($client && isset($client->connected) && $client->connected) {
                $client->push('ping');
            }
            \co::sleep($second);
        }
    }
}