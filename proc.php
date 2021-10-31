<?php
$pipes_holder = [];

function writeFileAsync($fileName, $text, $callback)
{
    global $pipes_holder;
    $process = popen("php writeFile.php $fileName \"$text\"", "r");

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
    $process = popen("php readFile.php $fileName", "r");

    stream_set_blocking($process, false);
    $pipes_holder[(int)$process] = [
        'resource' => $process,
        'callback' => $callback,
        'data' => null
    ];
}
