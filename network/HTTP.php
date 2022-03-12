<?php

class HTTP
{
    public static $multi_handlers = [];

    public static function fetch($method, $url, array $params)
    {
        return new Promise(function ($resolve, $reject) use (&$method, &$url, &$params) {
            self::httpRequest($method, $url, $params, $resolve, $reject);
        });
    }

    private static function httpRequest($method, $url, array $params, $callback, $err)
    {
        $multiHandlerAndCurlHandler = [];

        switch ($method) {
            case 'GET':
                $multiHandlerAndCurlHandler = HTTPRequester::HTTPGet($url, $params);
                break;
            case 'POST':
                $multiHandlerAndCurlHandler = HTTPRequester::HTTPPost($url, $params);
                break;
            case 'PUT':
                $multiHandlerAndCurlHandler = HTTPRequester::HTTPPut($url, $params);
                break;
            case 'DELETE':
                $multiHandlerAndCurlHandler = HTTPRequester::HTTPDelete($url, $params);
                break;
        }

        self::$multi_handlers[] = [
            'mh' => $multiHandlerAndCurlHandler[0],
            'ch' => $multiHandlerAndCurlHandler[1],
            'callback' => $callback,
            'err' => $err
        ];
    }
}
