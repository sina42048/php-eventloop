<?php
//error_reporting(E_ALL ^ E_WARNING);

require_once './process/File.php';
require_once './timer/Timer.php';
require_once './promise/Promise.php';
require_once './process/File.php';
require_once './async_await/Async.php';

pcntl_async_signals(true);

if (!function_exists("clearOnQuit")) {
    function clearOnQuit()
    {
        foreach (File::$pipes_holder as &$pipe) {
            if (getmypid() === $pipe['parent_pid']) {
                if ($pipe['shm_id'] > 0) {
                    @shmop_delete($pipe['shm_id']);
                    @shmop_close($pipe['shm_id']);
                }
            }
        }
        exit(0);
    }
}

function readProcess()
{
    $pipe = "/tmp/read";
    $pipe_main = "/tmp/readMain";
    posix_mkfifo($pipe, 0644);
    posix_mkfifo($pipe_main, 0644);
    $pid = pcntl_fork();

    if ($pid < 0) {
        echo "error";
    } else if ($pid === 0) {
        // child
        $fh = fopen($pipe, "r");
        $fh_main = fopen($pipe_main, "w");
        $read = [];
        $write = [];
        $except = null;

        stream_set_blocking($fh, false);
        while (true) {
            $read[] = $fh;
            if (stream_select($read, $write, $except, 1, 0)) {
                foreach ($read as &$r) {
                    $message = fgets($r);
                    if ($message) {
                        $message = explode("_", $message);
                        $randomNumber = trim($message[2]);
                        $fileName = trim($message[3]);
                        $message = trim($message[0]) . "_" . trim($message[1]);
                        switch ($message) {
                            case 'READ_REQUEST':
                                if (file_exists($fileName)) {
                                    $shm_id = shmop_open(42048, "c", 0644, (int)filesize($fileName));
                                    $file = fopen($fileName, "r");
                                    flock($file, LOCK_EX);
                                    $offset = 0;
                                    while (!feof($file)) {
                                        shmop_write($shm_id, stream_get_contents($file), $offset);
                                        $offset = 8193;
                                    }
                                    fwrite($fh_main, "READ_SUCCESS_$fileName" . "_" . $randomNumber . PHP_EOL);
                                    flock($file, LOCK_UN);
                                    fclose($file);
                                    shmop_close($shm_id);
                                } else {
                                    fwrite($fh_main, "ERR_NOTFOUND_$fileName" . "_" . $randomNumber . PHP_EOL);
                                }
                                break;
                        }
                    }
                }
            }
        }
    } else {
        // parent

        // do nothing
    }
}


function writeProcess()
{
    $pipe = "/tmp/write";
    $pipe_main = "/tmp/writeMain";
    posix_mkfifo($pipe, 0644);
    posix_mkfifo($pipe_main, 0644);
    $pid = pcntl_fork();

    if ($pid < 0) {
        echo "error";
    } else if ($pid === 0) {
        // child
        $fh = fopen($pipe, "r");
        $fh_main = fopen($pipe_main, "w");
        $read = [];
        $write = [];
        $except = null;

        stream_set_blocking($fh, false);
        while (true) {
            $read[] = $fh;

            if (stream_select($read, $write, $except, 1, 0)) {
                foreach ($read as &$r) {
                    $message = fgets($r);
                    if ($message) {
                        $message = explode("_", $message);
                        $randomNumber = trim($message[2]);
                        $fileName = trim($message[3]);
                        $text_length = trim($message[4]);
                        $message = trim($message[0]) . "_" . trim($message[1]);

                        switch ($message) {
                            case 'WRITE_REQUEST':
                                $shm_id = shmop_open(42050, "c", 0644, (int)$text_length);
                                $file = fopen($fileName, "w");
                                flock($file, LOCK_EX);
                                fwrite($file, shmop_read($shm_id, 0, 0));
                                fwrite($fh_main, "WRITE_SUCCESS_$fileName" . "_" . $randomNumber . PHP_EOL);
                                flock($file, LOCK_UN);
                                fclose($file);
                                shmop_close($shm_id);
                                break;
                        }
                    }
                }
            }
        }
    } else {
        // parent

        // do nothing
    }
}

readProcess();
writeProcess();
