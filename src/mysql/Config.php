<?php


namespace qzzm\mysql;

use qzzm\utlis\Singleton;

final class Config
{
    use Singleton;

    private $host = '127.0.0.1';
    private $port = '3306';
    private $dbName;
    private $userName;
    private $password;
    private $prefix;
    private $charset = 'utf8mb4';


    public function setHost(string $value): Config
    {
        $this->host = $value;
        return $this;
    }

    public function setPort(string $value): Config
    {
        $this->port = $value;
        return $this;
    }

    public function setDbName(string $value): Config
    {
        $this->dbName = $value;
        return $this;
    }

    public function setUserName(string $value): Config
    {
        $this->userName = $value;
        return $this;
    }

    public function setPassword(string $value): Config
    {
        $this->password = $value;
        return $this;
    }

    public function setPrefix(string $value): Config
    {
        $this->prefix = $value;
        return $this;
    }

    public function setCharset(string $value): Config
    {
        $this->charset = $value;
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

}