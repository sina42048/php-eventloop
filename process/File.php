<?php
include './promise/Promise.php';

class File
{
    public static $pipes_holder = [];

    public static function writeFileAsync($fileName, $text)
    {
        $process = popen("php ./process/writeFile.php $fileName \"$text\"", "r");
        self::$pipes_holder[(int)$process] = [
            'resource' => $process,
            'data' => null
        ];

        $promise = new Promise(function ($resolve, $reject) use ($process) {
            if ($process) {
                self::$pipes_holder[(int)$process]['resolve'] = $resolve;
                self::$pipes_holder[(int)$process]['reject'] = $reject;
            } else {
                $reject("popen error");
                unset(self::$pipes_holder[(int)$process]);
            }
        });


        return $promise;
    }

    public static function readFileAsync($fileName)
    {
        $process = popen("php ./process/readFile.php $fileName", "r");

        self::$pipes_holder[(int)$process] = [
            'resource' => $process,
            'data' => null
        ];

        $promise = new Promise(function ($resolve, $reject) use ($process) {
            if ($process) {
                self::$pipes_holder[(int)$process]['resolve'] = $resolve;
                self::$pipes_holder[(int)$process]['reject'] = $reject;
            } else {
                $reject("popen error");
                unset(self::$pipes_holder[(int)$process]);
            }
        });

        return $promise;
    }
}
