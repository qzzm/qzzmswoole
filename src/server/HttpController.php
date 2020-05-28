<?php


namespace qzzm\server;


abstract class HttpController
{
    protected $request;
    protected $response;
    protected $method;
    protected $nowTime;
    protected $clientIp;
    protected $header;

    function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->method = $request->server['request_method'];
        $this->nowTime = $request->server['request_time'];
        $this->clientIp = $request->server['remote_addr'];
        $this->header = $request->header;
    }

    protected function input(string $key, $method = '')
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                return $this->request->get[$key] ?? null;
            case 'POST':
                return $this->request->post[$key] ?? null;
            default:
                return $this->request->get[$key] ? $this->request->get[$key] : ($this->request->post[$key] ? $this->request->post[$key] : null);
                break;
        }
    }
}