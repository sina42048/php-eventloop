<?php

class File
{
    public static $pipes_holder = [];

    public static function writeFileAsync($fileName, &$text)
    {
        $text = str_split($text, 8192);
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
        $random_number = rand(1, 10000);
        $pipe_name = "/tmp/pipe" . $random_number;
        posix_mkfifo($pipe_name, 0644);

        $pid = pcntl_fork();

        if ($pid < 0) {
            echo 'error';
        } else if ($pid === 0) {
            // child
            error_reporting(0);
            $pipe = fopen($pipe_name, "w");
            $file = fopen($fileName, "w");
            foreach ($text as $key => $t) {
                fwrite($file, $t, strlen($t));
                unset($text[$key]);
            }
            fwrite($pipe, "WRITE_SUCCESS");
            fclose($pipe);
            fclose($file);
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
        $random_number = rand(1, 10000);
        $pipe_name = "/tmp/pipe" . $random_number;
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
                $shm_id = shmop_open($random_number, "c", 0644, filesize($fileName));
                $offset = 0;
                while (!feof($file)) {
                    shmop_write($shm_id, fread($file, 8192), $offset);
                    $offset += 8193;
                }
                fwrite($pipe, "READ_SUCCESS");
                fclose($file);
                fclose($pipe);
            } else {
                fwrite($pipe, 'ERR_NOT_FOUND');
            }
            exit(0);
        } else {
            // parent
            usleep(15);
            $shm_id = -1;
            if (file_exists($fileName)) {
                $shm_id = shmop_open($random_number, "c", 0644, filesize($fileName));
            }
            $pipe = fopen($pipe_name, "r");
            stream_set_blocking($pipe, false);

            self::$pipes_holder[(int)$pipe] = [
                'parent_pid' => getmypid(),
                'resource' => $pipe,
                'shm_id' => $shm_id,
                'callback' => $callback,
                'file' => $pipe_name,
                'err' => $err,
                'data' => null
            ];
        }
    }
}
