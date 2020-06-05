<?php

namespace qzzm\factory;

use qzzm\mysql\Config;
use qzzm\mysql\DbPool;
use qzzm\utlis\Config as rConfig;
use qzzm\utlis\Singleton;

final class Register
{
    use Singleton;

    /**
     * 注册mysql连接
     */
    public function mysql()
    {
        $configs = rConfig::getInstance()->get('MYSQL');
        if ($configs && gettype($configs) === 'array') {
            //判断一维数组或二维数组
            if (count($configs) == count($configs, 1)) {
                DbPool::getInstance()->addConnection(
                    Config::getInstance()
                        ->setHost($configs['host'])
                        ->setDbName($configs['db'])
                        ->setUserName($configs['user'])
                        ->setPassword($configs['password'])
                        ->setPrefix($configs['prefix'])
                        ->setCharset($configs['charset']),
                    $configs['pool']
                );
            } else {
                foreach ($configs as $config) {
                    DbPool::getInstance()->addConnection(
                        Config::getInstance()
                            ->setHost($config['host'])
                            ->setDbName($config['db'])
                            ->setUserName($config['user'])
                            ->setPassword($config['password'])
                            ->setPrefix($config['prefix'])
                            ->setCharset($config['charset']),
                        $config['pool']
                    );
                }
            }
        }
    }

    /**
     * 注册redis连接
     */
    public function redis()
    {

    }

    /**
     * 注册跨域
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function cross(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $configs = rConfig::getInstance()->get('MYSQL');

        if (isset($configs['domains']) && $configs['domains']) {
            if ($configs['domains'] === '*') {
                $response->setHeader('Access-Control-Allow-Origin', '*');
            } else {
                $host = $request->header['host'] ?? null;
                if (in_array($host, $configs['domains'])) {
                    $response->setHeader('Access-Control-Allow-Origin', $host);
                }
            }
        }

        if (isset($configs['methods']) && $configs['methods']) {
            $methods = implode(',', $configs['methods']);
            $response->setHeader('Access-Control-Allow-Methods', $methods);
        }

//        $response->withHeader('Access-Control-Allow-Credentials', 'true');

        if (isset($configs['headers']) && $configs['headers']) {
            $headers = implode(',', $configs['headers']);
            $response->setHeader('Access-Control-Allow-Headers', $headers);
        }

    }
}