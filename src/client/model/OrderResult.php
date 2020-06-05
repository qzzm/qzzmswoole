<?php


namespace qzzm\client\model;

use qzzm\utlis\JsonSerialize;

class OrderResult extends JsonSerialize
{
    protected $url;
    protected $token;
    protected $data;
    protected $toFd;
    protected $platform = 'client';

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getToFd(): int
    {
        return $this->toFd;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setUrl(string $value): void
    {
        $this->url = $value;
    }

    public function setToken(string $value): void
    {
        $this->token = $value;
    }

    public function setToFd(int $value = 0): void
    {
        $this->toFd = $value;
    }

    public function setData(array $value): void
    {
        $this->data = $value;
    }

    public function setPlatform(string $value): void
    {
        $this->platform = $value;
    }
}