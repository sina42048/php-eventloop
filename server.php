<?php
require_once './EventLoop.php';
require_once './promise/Promise.php';

$loop = new EventLoop();

// promise based timer example
$promiseTimeout = (new Promise(function ($resolve) use ($loop) {
    $loop->setTimeout($resolve, 1000);
}))->then(function () {
    echo 'Promise based Timer ' . PHP_EOL;
});

$timeout = $loop->setTimeout(function () use (&$timeout, &$loop) {
    echo "Hello after 1 second !" . PHP_EOL;
    $loop->clearTimeout($timeout);
}, 1000);

$interval = $loop->setInterval(function () {
    echo "Tick Tock" . PHP_EOL;
}, 1000);


$loop->writeFileAsync("test.txt", "hello from test.txt file")->then(function ($data) {
    echo $data . PHP_EOL;
})->catch(function ($error) {
    echo $error . PHP_EOL;
});

$loop->setTimeout(function () use ($interval, &$loop) {
    $loop->readFileAsync("test.txt")->then(function ($data) use ($interval, &$loop) {
        echo $data . PHP_EOL;
        $loop->setImmediate(function () {
            echo "i will happen end of loop when file read complete !" . PHP_EOL;
        });
        // interval now cleared !
        $loop->clearInterval($interval);
        $loop->setInterval(function () {
            echo "Replaced interval" . PHP_EOL;
        }, 1000);
    })->catch(function ($error) {
        echo $error . PHP_EOL;
    });
}, 4000);

$loop->createServer("tcp://127.0.0.1:8080")->then(function ($ip) {
    echo 'Server Started at port : ' . $ip . PHP_EOL;
})->catch(function ($error) {
    echo $error;
});

$loop->run();
