<?php

$timers = [];
$futureTicks = [];

function setTimeout($callback, $delay)
{
    global $timers;
    $delay = $delay === 0 ? 0.1 : $delay;
    $timers[] = [
        'type' => 'timeout',
        'time' => hrtime(true) + ($delay * 1000000),
        'callback' => $callback,
        'happend' => false
    ];
    return array_key_last($timers);
}

function setInterval($callback, $delay)
{
    global $timers;
    $delay = $delay === 0 ? 0.1 : $delay;
    $timers[] = [
        'type' => 'interval',
        'delay' => ($delay * 1000000),
        'time' => hrtime(true) + ($delay * 1000000),
        'callback' => $callback,
        'happend' => false // never change
    ];
    return array_key_last($timers);
}

function setImmediate($callback)
{
    global $futureTicks;
    $futureTicks[] = [
        'callback' => $callback
    ];
}

function clearTimeout($id)
{
    global $timers;
    unset($timers[$id]);
}

function clearInterval($id)
{
    global $timers;
    unset($timers[$id]);
}

setImmediate(function () {
    echo 'immediate' . PHP_EOL;
});

$timeout1 = setTimeout(function () {
    echo 'timeout' . PHP_EOL;
}, 3000);

$interval1 = setInterval(function () {
    echo 'interval' . PHP_EOL;
}, 500);
