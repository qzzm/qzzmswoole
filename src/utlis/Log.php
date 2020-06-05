<?php


namespace qzzm\utlis;


final class Log
{
    public static function dump($val, bool $trace = false): void
    {
        $arr = [31, 32, 33, 35, 36];
        $color = $arr[array_rand($arr, 1)];
        $array = debug_backtrace();

        if ($trace) {
            $arrRev = array_reverse($array);
            foreach ($arrRev as $row) {  //需要一路追踪方法调用的小伙伴用这个
                $line = $row['line'];
                $file = $row['file'];
                echo "\e[0;34m{$file}:{$line}\e[0m\n";
            }
            echo "\e[0;{$color}mLine:{$line} --------------- Begin\e[0m\n";
            var_dump($val);
            echo "\e[0;{$color}mLine:{$line} --------------- End\e[0m\n\n";
        } else {
            $row = $array ? $array[0] : [];
            if ($row) {
                $line = $row['line'];
                $file = $row['file'];
                echo "\e[0;34m{$file}:{$line}\e[0m\n";
                echo "\e[0;{$color}mLine:{$line} --------------- Begin\e[0m\n";
                var_dump($val);
                echo "\e[0;{$color}mLine:{$line} --------------- End\e[0m\n\n";
            }
        }
    }

    public static function save(string $content)
    {
        \Swoole\Coroutine\System::writeFile('./1.log', $content . "\r\n", FILE_APPEND);
    }
}