<?php
error_reporting(E_ALL ^ (E_WARNING | E_DEPRECATED | E_NOTICE));
ini_set("memory_limit", -1);

require_once './file/File.php';
require_once './network/HTTPRequester.php';
require_once './network/HTTP.php';
require_once './timer/Timer.php';
require_once './promise/Promise.php';
require_once './async_await/Async.php';

function readAndWriteProcess()
{
    $pipe = "/tmp/pipe";
    $pipe_main = "/tmp/pipe_main";
    posix_mkfifo($pipe, 0644);
    posix_mkfifo($pipe_main, 0644);

    $pid = pcntl_fork();

    if ($pid < 0) {
        echo "error creating process";
    } else if ($pid === 0) {
        // child
        pcntl_async_signals(true);

        $shms_container = [];
        $fh = fopen($pipe, "r");
        $fh_main = fopen($pipe_main, "w");
        $read = [];
        $write = [];
        $except = null;

        $clearOnQuit = function () use (&$shms_container) {
            foreach ($shms_container as $shm) {
                shmop_delete($shm);
                shmop_close($shm);
            }
            exit(0);
        };

        pcntl_signal(SIGQUIT, $clearOnQuit);
        pcntl_signal(SIGTERM, $clearOnQuit);
        pcntl_signal(SIGINT, $clearOnQuit);

        stream_set_blocking($fh, false);
        while (true) {
            $read[] = $fh;

            if (@stream_select($read, $write, $except, null, 0)) {
                foreach ($read as &$r) {
                    $message = fgets($r);
                    if ($message) {
                        $message = explode("_+_", $message);
                        $randomNumber = trim($message[2]);
                        $fileName = trim($message[3]);
                        $text_length = isset($message[4]) ? trim($message[4]) : null;
                        $message = trim($message[0]) . "_" . trim($message[1]);

                        switch ($message) {
                            case 'WRITE_REQUEST':
                                $shm_id = shmop_open($randomNumber, "c", 0644, (int)$text_length);
                                $file = fopen($fileName, "w");
                                $shms_container[$randomNumber] = $shm_id;
                                fwrite($file, shmop_read($shm_id, 0, 0));
                                fwrite($fh_main, "WRITE_+_SUCCESS_+_$fileName" . "_+_" . $randomNumber . PHP_EOL);
                                fclose($file);
                                shmop_delete($shm_id);
                                shmop_close($shm_id);
                                break;
                            case 'READ_REQUEST':
                                if (file_exists($fileName)) {
                                    $file = fopen($fileName, "r");
                                    $fileSize = (int)filesize($fileName);
                                    $shm_id = shmop_open($randomNumber, "c", 0644, $fileSize);
                                    $shms_container[$randomNumber] = $shm_id;
                                    shmop_write($shm_id, stream_get_contents($file), 0);
                                    fwrite($fh_main, "READ_+_SUCCESS_+_$fileName" . "_+_" . $randomNumber . "_+_" . $fileSize . PHP_EOL);
                                    fclose($file);
                                    shmop_close($shm_id);
                                } else {
                                    fwrite($fh_main, "ERR_+_NOTFOUND_+_$fileName" . "_+_" . $randomNumber . PHP_EOL);
                                }
                                break;
                            case 'APPEND_REQUEST':
                                $shm_id = shmop_open($randomNumber, "c", 0644, (int)$text_length);
                                $file = fopen($fileName, "a");
                                $shms_container[$randomNumber] = $shm_id;
                                fwrite($file, shmop_read($shm_id, 0, 0));
                                fwrite($fh_main, "APPEND_+_SUCCESS_+_$fileName" . "_+_" . $randomNumber . PHP_EOL);
                                fclose($file);
                                shmop_delete($shm_id);
                                shmop_close($shm_id);
                                break;
                            case 'DELETE_REQUEST':
                                if (file_exists($fileName)) {
                                    unlink($fileName);
                                    fwrite($fh_main, "DELETE_+_SUCCESS_+_$fileName" . "_+_" . $randomNumber . PHP_EOL);
                                } else {
                                    fwrite($fh_main, "ERR_+_NOTFOUND_+_$fileName" . "_+_" . $randomNumber . PHP_EOL);
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

readAndWriteProcess();
