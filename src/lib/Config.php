<?php


namespace qzzm\lib;


class Config
{
    use Singleton;

    public function get(string $key)
    {
        $config = require QZZM_ROOT . '/config.php';
        if (strpos($key, ".") > 0) {
            $arrs = explode('.', $key);
            foreach ($arrs as $arr) {
                $config = $config[$arr];
            }
            return $config;
        } else {
            return $config[$key];
        }
    }

    public function getAll()
    {
        return require QZZM_ROOT . '/config.php';
    }
}