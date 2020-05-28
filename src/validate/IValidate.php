<?php


namespace qzzm\validate;


interface IValidate
{
    /**
     * 规则
     * @return array
     */
    function rule(): array;

    /**
     * 字段场景
     * @return array
     */
    function scene(): array;
}