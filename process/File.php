<?php

class File
{
    public static $pipes_holder = [];
    public static $communicationPipes = [];

    public static function writeFileAsync($fileName, &$text)
    {
        return new Promise(function ($resolve, $reject) use ($fileName, &$text) {
            self::write($fileName, $text, $resolve, $reject);
        });
    }

    public static function readFileAsync($fileName)
    {
        return new Promise(function ($resolve, $reject) use ($fileName) {
            self::read($fileName, $resolve, $reject);
        });
    }

    private static function read($fileName, $callback, $err)
    {
        $random_number = rand(1, 100000);
        $writer = array_slice(self::$communicationPipes, 0, 1);
        $message = "READ_REQUEST_" . $random_number . "_" . $fileName . PHP_EOL;
        $shm_id = -1;
        $fileSize = -1;
        if (file_exists($fileName)) {
            $fileSize = filesize($fileName);
            $shm_id = shmop_open(42048, "c", 0644, (int)$fileSize);
        }
        fwrite($writer[0], $message);
        self::$pipes_holder[$random_number] = [
            'parent_pid' => getmypid(),
            "fileSize" => $fileSize,
            'shm_id' => $shm_id,
            'callback' => $callback,
            'err' => $err,
        ];
    }

    private static function write($fileName, $text, $callback, $err)
    {
        if (strlen($text) <= 1000000000) {
            $random_number = rand(1, 100000);
            $writer = array_slice(self::$communicationPipes, 2, 1);
            $shm_id = shmop_open(42050, "c", 0644, (int)strlen($text));
            shmop_write($shm_id, $text, 0);
            $message = "WRITE_REQUEST_" . $random_number . "_" . $fileName . "_" . strlen($text) . PHP_EOL;
            $text = '';
            fwrite($writer[0], $message);

            self::$pipes_holder[$random_number] = [
                'parent_pid' => getmypid(),
                'shm_id' => $shm_id,
                'callback' => $callback,
            ];
        } else {
            call_user_func($err, "string length is too long !");
        }
    }

    # *** OLD CODE ***

    // private static function write($fileName, &$text, $callback)
    // {
    //     $random_number = rand(1, 10000);
    //     $pipe_name = "/tmp/pipe" . $random_number;
    //     posix_mkfifo($pipe_name, 0644);

    //     $pid = pcntl_fork();

    //     if ($pid < 0) {
    //         echo 'error';
    //     } else if ($pid === 0) {
    //         // child
    //         error_reporting(0);

    //         $pipe = fopen($pipe_name, "w");
    //         $semaphore = sem_get(42048, 1, 0666, 1);
    //         $file = fopen($fileName, "w");

    //         if (sem_acquire($semaphore)) {
    //             flock($file, LOCK_EX);
    //             foreach ($text as $key => $t) {
    //                 fwrite($file, $t, strlen($t));
    //                 unset($text[$key]);
    //             }
    //             sem_release($semaphore);
    //             flock($file, LOCK_UN);
    //             fwrite($pipe, "WRITE_SUCCESS");
    //             fclose($file);
    //             $text = '';
    //             exit(0);
    //         }
    //     } else {
    //         // parent
    //         usleep(15);
    //         $pipe = fopen($pipe_name, "r");
    //         stream_set_blocking($pipe, false);
    //         $text = '';
    //         self::$pipes_holder[(int)$pipe] = [
    //             'resource' => $pipe,
    //             'callback' => $callback,
    //             'file' => $pipe_name,
    //             'data' => null
    //         ];
    //     }
    // }

    // private static function read($fileName, $callback, $err)
    // {
    //     $random_number = rand(1, 10000);
    //     $pipe_name = "/tmp/pipe" . $random_number;
    //     posix_mkfifo($pipe_name, 0644);

    //     $pid = pcntl_fork();

    //     if ($pid < 0) {
    //         echo 'error';
    //     } else if ($pid === 0) {
    //         // child
    //         error_reporting(0);

    //         $pipe = fopen($pipe_name, "w");
    //         $current = hrtime(true);
    //         if (file_exists($fileName)) {
    //             $semaphore = sem_get(42050, 1, 0666, true);
    //             $shm_id = shmop_open($random_number, "c", 0644, filesize($fileName));
    //             $file = fopen($fileName, "r");
    //             if (sem_acquire($semaphore, false)) {
    //                 flock($file, LOCK_EX);
    //                 $offset = 0;
    //                 while (true) {
    //                     if (feof($file)) {
    //                         break;
    //                     }
    //                     shmop_write($shm_id, fread($file, 65536), $offset);
    //                     $offset += 65537;
    //                 }
    //                 flock($file, LOCK_UN);
    //                 fwrite($pipe, "READ_SUCCESS");
    //                 fclose($file);
    //                 sem_release($semaphore);
    //             }
    //         } else {
    //             fwrite($pipe, 'ERR_NOT_FOUND');
    //         }
    //         echo hrtime(true) - $current . PHP_EOL;
    //         exit(0);
    //     } else {
    //         // parent
    //         usleep(15);
    //         $shm_id = -1;
    //         if (file_exists($fileName)) {
    //             $shm_id = shmop_open($random_number, "c", 0644, filesize($fileName));
    //         }
    //         $pipe = fopen($pipe_name, "r");
    //         stream_set_blocking($pipe, false);

    //         self::$pipes_holder[(int)$pipe] = [
    //             'parent_pid' => getmypid(),
    //             'resource' => $pipe,
    //             'shm_id' => $shm_id,
    //             'callback' => $callback,
    //             'file' => $pipe_name,
    //             'err' => $err,
    //             'data' => null
    //         ];
    //     }
    // }
}
