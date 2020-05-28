<?php

namespace app\http\controller;

use qzzm\server\HttpController;

abstract class AController extends HttpController
{
    private $token;

    function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        parent::__construct($request, $response);
        $this->token = $this->header['qzzm-token'] ?? null;
        $this->checkToken($this->token);
    }

    private function checkToken(?string $token)
    {

    }
}