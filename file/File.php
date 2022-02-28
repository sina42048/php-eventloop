<?php

class File
{
    public static $operations_holder = [];
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

    public static function appendFileAsync($fileName, &$text)
    {
        return new Promise(function ($resolve, $reject) use ($fileName, &$text) {
            self::append($fileName, $text, $resolve, $reject);
        });
    }

    public static function deleteFIleAsync($fileName)
    {
        return new Promise(function ($resolve, $reject) use ($fileName) {
            self::delete($fileName, $resolve, $reject);
        });
    }

    private static function read($fileName, $callback, $err)
    {
        $random_number = rand(1, 100000);
        $writer = array_slice(self::$communicationPipes, 0, 1);
        $message = "READ_+_REQUEST_+_" . $random_number . "_+_" . $fileName . PHP_EOL;

        fwrite($writer[0], $message);
        self::$operations_holder[$random_number] = [
            'callback' => $callback,
            'err' => $err,
        ];
    }

    private static function write($fileName, $text, $callback, $err)
    {
        if (strlen($text) <= 1000000000) {
            $random_number = rand(1, 100000);
            $writer = array_slice(self::$communicationPipes, 0, 1);
            $shm_id = shmop_open($random_number, "c", 0644, (int)strlen($text));
            shmop_write($shm_id, $text, 0);
            shmop_close($shm_id);
            $message = "WRITE_+_REQUEST_+_" . $random_number . "_+_" . $fileName . "_+_" . strlen($text) . PHP_EOL;
            fwrite($writer[0], $message);
            $text = '';

            self::$operations_holder[$random_number] = [
                'callback' => $callback,
            ];
        } else {
            $text = '';
            call_user_func($err, "string length is too long !");
        }
    }

    private static function append($fileName, $text, $callback, $err)
    {
        if (strlen($text) <= 1000000000) {
            $random_number = rand(1, 100000);
            $writer = array_slice(self::$communicationPipes, 0, 1);
            $shm_id = shmop_open($random_number, "c", 0644, (int)strlen($text));
            shmop_write($shm_id, $text, 0);
            shmop_close($shm_id);
            $message = "APPEND_+_REQUEST_+_" . $random_number . "_+_" . $fileName . "_+_" . strlen($text) . PHP_EOL;
            fwrite($writer[0], $message);
            $text = '';

            self::$operations_holder[$random_number] = [
                'callback' => $callback,
            ];
        } else {
            $text = '';
            call_user_func($err, "string length is too long !");
        }
    }

    private static function delete($fileName, $callback, $err)
    {
        $random_number = rand(1, 100000);
        $writer = array_slice(self::$communicationPipes, 0, 1);
        $message = "DELETE_+_REQUEST_+_" . $random_number . "_+_" . $fileName . PHP_EOL;

        fwrite($writer[0], $message);
        self::$operations_holder[$random_number] = [
            'callback' => $callback,
            'err' => $err,
        ];
    }
}
