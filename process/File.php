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
            $semaphore = sem_get(42048, 1, 0666, 1);
            $file = fopen($fileName, "w");

            if (sem_acquire($semaphore)) {
                flock($file, LOCK_EX);
                foreach ($text as $key => $t) {
                    fwrite($file, $t, strlen($t));
                    unset($text[$key]);
                }
                sem_release($semaphore);
                flock($file, LOCK_UN);
                fwrite($pipe, "WRITE_SUCCESS");
                fclose($file);
                $text = '';
                exit(0);
            }
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
            if (file_exists($fileName)) {
                $semaphore = sem_get(42050, 1, 0666, true);
                $shm_id = shmop_open($random_number, "c", 0644, filesize($fileName));
                $file = fopen($fileName, "r");
                if (sem_acquire($semaphore, false)) {
                    flock($file, LOCK_EX);
                    $offset = 0;
                    while (true) {
                        if (feof($file)) {
                            break;
                        }
                        shmop_write($shm_id, fread($file, 65536), $offset);
                        $offset += 65537;
                    }
                    flock($file, LOCK_UN);
                    fwrite($pipe, "READ_SUCCESS");
                    fclose($file);
                    sem_release($semaphore);
                }
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
