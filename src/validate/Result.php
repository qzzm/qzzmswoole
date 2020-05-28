<?php


namespace qzzm\validate;


class Result
{
    private $flag = false;
    private $error = '';

    public function setFlag(bool $value): void
    {
        $this->flag = $value;
    }

    public function setError(string $value): void
    {
        $this->error = $value;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }

    public function getError(): string
    {
        return $this->error;
    }
}