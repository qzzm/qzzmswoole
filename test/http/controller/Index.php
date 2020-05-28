<?php


namespace app\http\controller;


class Index extends AController
{
    public function index()
    {
        return ['code' => 0, 'msg' => 'Hello Qzzm!'];
    }
}