<?php
error_reporting(E_ALL ^ E_WARNING);

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
                shmop_delete($pipe['shm_id']);
                shmop_close($pipe['shm_id']);
            }
        }
        exit(0);
    }
}
