<?php


namespace qzzm\server\model;


use qzzm\lib\JsonSerialize;

final class OrderClient extends JsonSerialize
{
    protected $url;
    protected $data;
    protected $fromFd;
    protected $callbackUrl;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getFromFd(): int
    {
        return $this->fromFd;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function setUrl(string $value): void
    {
        $this->url = $value;
    }

    public function setData(array $value): void
    {
        $this->data = $value;
    }

    public function setFromFd(int $value = 0): void
    {
        $this->fromFd = $value;
    }

    public function setCallbackUrl(string $value): void
    {
        $this->callbackUrl = $value;
    }

}