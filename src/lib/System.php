<?php

namespace qzzm\lib;

class System
{
    public static function mk_dir($dir, $mode = 0755)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true;
        if (!self::mk_dir(dirname($dir), $mode)) return false;
        return @mkdir($dir, $mode);
    }
}