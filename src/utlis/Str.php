<?php


namespace qzzm\utlis;


final class Str
{
    /*
     * 驼峰转下划线
     */
    public static function humpToLine($str)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $str));
    }

    /*
     * 下划线转驼峰
     */
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/_+([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $str);
        return $str;
    }
}