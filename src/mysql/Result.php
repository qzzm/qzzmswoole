<?php


namespace qzzm\mysql;


class Result
{
    private $flag = false;
    private $error = '';
    private $sql = '';
    private $result;

    public function setFlag(bool $value): void
    {
        $this->flag = $value;
    }

    public function setError( $value): void
    {
        $this->error = $value;
    }

    public function setSql($value): void
    {
        $this->sql = $value;
    }

    public function setResult($value): void
    {
        $this->result = $value;
    }

    /**
     * 执行的SQL是否正确
     * @return bool
     */
    public function getFlag(): bool
    {
        return $this->flag;
    }

    /**
     * SQL的错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQL的拼装后语句
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * 执行sql返回的结果 INSERT=自增id  UPDATE|DELETE=作用行数  SELECT=查询结果
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}