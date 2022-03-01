<?php

class HTTP
{
    public static $operations_holder = [];
    public static $communicationPipes = [];

    public static function get($url, array $params)
    {
        return new Promise(function ($resolve, $reject) use ($url, &$params) {
            self::httpRequest('GET', $url, $params, $resolve, $reject);
        });
    }

    public static function post($url, array $params)
    {
        return new Promise(function ($resolve, $reject) use ($url, &$params) {
            self::httpRequest('POST', $url, $params, $resolve, $reject);
        });
    }

    public static function put($url, array $params)
    {
        return new Promise(function ($resolve, $reject) use ($url, &$params) {
            self::httpRequest('PUT', $url, $params, $resolve, $reject);
        });
    }


    public static function delete($url, array $params)
    {
        return new Promise(function ($resolve, $reject) use ($url, &$params) {
            self::httpRequest('DELETE', $url, $params, $resolve, $reject);
        });
    }

    private static function httpRequest($method, $url, array $params, $callback, $err)
    {
        $random_number = rand(1, 10000);
        $pipe_name = "/tmp/httpPipe" . $random_number;
        posix_mkfifo($pipe_name, 0644);
        $pid = pcntl_fork();

        if ($pid < 0) {
            echo 'error';
        } else if ($pid === 0) {
            // child
            error_reporting(0);

            $pipe = fopen($pipe_name, "w");
            stream_set_blocking($pipe, false);

            switch ($method) {
                case 'GET':
                    $response = HTTPRequester::HTTPGet($url, $params);
                    if (!$response) {
                        fwrite($pipe, "HTTP_+_GET_+_FAILED_+_" . $response . "_+_" . PHP_EOL);
                    } else {
                        fwrite($pipe, "HTTP_+_GET_+_RESPONSE_+_" . $response . "_+_" . PHP_EOL);
                    }
                    break;
                case 'POST':
                    $response = HTTPRequester::HTTPPost($url, $params);
                    if (!$response) {
                        fwrite($pipe, "HTTP_+_POST_+_FAILED_+_" . $response . "_+_" . PHP_EOL);
                    } else {
                        fwrite($pipe, "HTTP_+_POST_+_RESPONSE_+_" . $response . "_+_" . PHP_EOL);
                    }
                    break;
                case 'PUT':
                    $response = HTTPRequester::HTTPPUT($url, $params);
                    if (!$response) {
                        fwrite($pipe, "HTTP_+_PUT_+_FAILED_+_" . $response . "_+_" . PHP_EOL);
                    } else {
                        fwrite($pipe, "HTTP_+_PUT_+_RESPONSE_+_" . $response . "_+_" . PHP_EOL);
                    }
                    break;
                case 'DELETE':
                    $response = HTTPRequester::HTTPDelete($url, $params);
                    if (!$response) {
                        fwrite($pipe, "HTTP_+_DELETE_+_FAILED_+_" . $response . "_+_" . PHP_EOL);
                    } else {
                        fwrite($pipe, "HTTP_+_DELETE_+_RESPONSE_+_" . $response . "_+_" . PHP_EOL);
                    }
                    break;
            }

            fclose($pipe);
            unlink($pipe_name);

            exit(0);
        } else {
            // parent
            $pipe = fopen($pipe_name, "r+");
            stream_set_blocking($pipe, false);

            self::$communicationPipes[(int)$pipe] = $pipe;
            self::$operations_holder[(int)$pipe] = [
                'callback' => $callback,
                'err' => $err,
            ];
        }
    }
}
