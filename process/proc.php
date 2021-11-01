<?php
$pipes_holder = [];

function writeFileAsync($fileName, $text, $callback)
{
    global $pipes_holder;
    $process = popen("php ./process/writeFile.php $fileName \"$text\"", "r");

    stream_set_blocking($process, false);
    $pipes_holder[(int)$process] = [
        'resource' => $process,
        'callback' => $callback,
        'data' => null
    ];
}

function readFileAsync($fileName, $callback)
{
    global $pipes_holder;
    $process = popen("php ./process/readFile.php $fileName", "r");

    stream_set_blocking($process, false);
    $pipes_holder[(int)$process] = [
        'resource' => $process,
        'callback' => $callback,
        'data' => null
    ];
}
