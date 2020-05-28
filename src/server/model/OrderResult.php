<?php


namespace qzzm\server\model;


use qzzm\lib\JsonSerialize;

class OrderResult extends JsonSerialize
{
    protected $code;
    protected $action;
    protected $error;
    protected $data;

    public function setCode(int $value): void
    {
        $this->code = $value;
    }

    public function setAction(string $value): void
    {
        $this->action = $value;
    }

    public function setData(array $value): void
    {
        $this->data = $value;
    }

    public function setError(string $value): void
    {
        $this->error = $value;
    }
}