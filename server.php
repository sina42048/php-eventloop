<?php
require_once './EventLoop.php';

$loop = new EventLoop();

$loop->setTimeout(function () {
    HTTP::get("https://jsonplaceholder.typicode.com/todos/1", [])
        ->then(function ($response) {
            var_dump($response);
        })->catch(function ($err) {
            echo $err . PHP_EOL;
        });
    HTTP::post("https://jsonplaceholder.typicode.com/posts", [
        "title" => "title number 1",
        "body" => "description"
    ])->then(function ($response) {
        var_dump($response);
    })->catch(function ($err) {
        echo $err . PHP_EOL;
    });
    HTTP::put("https://jsonplaceholder.typicode.com/posts/1", [
        "id" => 1,
        "title" => "title number 1",
        "body" => "description"
    ])->then(function ($response) {
        var_dump($response);
    })->catch(function ($err) {
        echo $err . PHP_EOL;
    });
    HTTP::delete("https://jsonplaceholder.typicode.com/posts/1", [])->then(function ($response) {
        echo "POST NUMBER 1 DELETED SUCCESS" . PHP_EOL;
    })->catch(function ($err) {
        echo $err . PHP_EOL;
    });
}, 2000);

// promise based timer example
$promiseTimeout = (new Promise(function ($resolve) use (&$loop) {
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
}, 500);

$loop->setTimeout(function () use (&$loop) {
    $loop->appendFileAsync("test.txt", str_repeat("Bye", 2000000))->then(function ($data) {
        echo $data . PHP_EOL;
    })->catch(function ($err) {
        echo $err . PHP_EOL;
    });
}, 5500);

$loop->setTimeout(function () use (&$loop) {
    $loop->writeFileAsync("willBeDelete.txt", str_repeat("delete", 5000))->then(function () use (&$loop) {
        $loop->deleteFIleAsync("willBeDelete.txt")->then(function ($data) {
            echo $data . PHP_EOL;
        })->catch(function ($err) {
            echo $err . PHP_EOL;
        });
    })->catch(function ($err) {
        echo $err;
    });
}, 8000);

$loop->writeFileAsync("test.txt", str_repeat("Hello", 60000000))->then(function ($data) {
    echo $data . PHP_EOL;
})->catch(function ($error) {
    echo $error . PHP_EOL;
});

$loop->setTimeout(function () use ($interval, &$loop) {
    $loop->readFileAsync("test.txt")->then(function ($data) use ($interval, &$loop) {
        echo "read success" . PHP_EOL;
        # huge data, if u want to show on terminal then uncomment below line
        //echo $data . PHP_EOL;
        $loop->setImmediate(function () {
            echo "i will happen end of loop when file read complete !" . PHP_EOL;
        });
        // interval now cleared !
        $loop->clearInterval($interval);
        $loop->setInterval(function () {
            echo "Replaced interval" . PHP_EOL;
        }, 500);
    })->catch(function ($error) {
        echo $error . PHP_EOL;
    });
}, 4000);

$loop->createServer("tcp://127.0.0.1:8080")->then(function ($ip) {
    echo 'Server Started at port : ' . $ip . PHP_EOL;
})->catch(function ($error) {
    echo $error;
});

//async await style
Async::run(function () use ($loop) {
    yield Async::delay(2000);
    echo 'async/await style after two second' . PHP_EOL;
    yield Async::delay(3000);
    echo 'async/await style after five second' . PHP_EOL;
    try {
        $readFile = yield $loop->readFileAsync("notExist.txt"); // will be thrown an error
        echo $readFile . " => async / await" . PHP_EOL; //  not being executed
    } catch (Error $err) {
        echo $err->getMessage() . " async / await " . PHP_EOL;
    }
    yield Async::delay(5000);
    return 'async/await style final delay' . PHP_EOL;
});

$loop->run();
