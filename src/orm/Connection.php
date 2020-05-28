<?php


namespace qzzm\orm;


use qzzm\lib\Singleton;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

final class Connection
{
    use Singleton;

    public function addPool(Config $config, string $poolName = 'mysql'): PDOPool
    {
        $pool = new PDOPool((new PDOConfig)
            ->withHost($config->getHost())
            ->withPort($config->getPort())
            // ->withUnixSocket('/tmp/mysql.sock')
            ->withDbName($config->getDbName())
            ->withCharset($config->getCharset())
            ->withUsername($config->getUserName())
            ->withPassword($config->getPassword())
        );
        return $pool;
    }
}