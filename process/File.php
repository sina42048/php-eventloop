<?php

class File
{
    public static $pipes_holder = [];

    public static function writeFileAsync($fileName, &$text)
    {
        return new Promise(function ($resolve, $reject) use ($fileName, &$text) {
            self::write($fileName, $text, $resolve);
        });
    }

    public static function readFileAsync($fileName)
    {
        return new Promise(function ($resolve, $reject) use ($fileName) {
            self::read($fileName, $resolve, $reject);
        });
    }

    private static function write($fileName, &$text, $callback)
    {
        $pipe_name = "/tmp/pipe" . rand();
        posix_mkfifo($pipe_name, 0644);

        $pid = pcntl_fork();

        if ($pid < 0) {
            echo 'error';
        } else if ($pid === 0) {
            // child
            error_reporting(0);
            $pipe = fopen($pipe_name, "w");
            $file = fopen($fileName, "w");
            fwrite($file, $text);
            fwrite($pipe, "WRITE_SUCCESS");
            fclose($pipe);
            $text = '';
            exit(0);
        } else {
            // parent
            usleep(15);
            $pipe = fopen($pipe_name, "r");
            stream_set_blocking($pipe, false);
            $text = '';
            self::$pipes_holder[(int)$pipe] = [
                'resource' => $pipe,
                'callback' => $callback,
                'file' => $pipe_name,
                'data' => null
            ];
        }
    }

    private static function read($fileName, $callback, $err)
    {
        $pipe_name = "/tmp/pipe" . rand();
        posix_mkfifo($pipe_name, 0644);

        $pid = pcntl_fork();

        if ($pid < 0) {
            echo 'error';
        } else if ($pid === 0) {
            // child
            error_reporting(0);

            $pipe = fopen($pipe_name, "w");
            $file = fopen($fileName, "r");
            if ($file) {
                while (!feof($file)) {
                    $content = stream_get_contents($file, 8192);
                    fwrite($pipe, $content);
                }
                fclose($file);
                fclose($pipe);
            } else {
                fwrite($pipe, 'ERR_NOT_FOUND');
            }
            exit(0);
        } else {
            // parent
            usleep(15);
            $pipe = fopen($pipe_name, "r");
            stream_set_blocking($pipe, false);

            self::$pipes_holder[(int)$pipe] = [
                'resource' => $pipe,
                'callback' => $callback,
                'file' => $pipe_name,
                'err' => $err,
                'data' => null
            ];
        }
    }
}
