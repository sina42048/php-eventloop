# simple php event loop ( select system call / non blocking io)
simple php event loop to demonstrate how event loop work behind the scene

```
*** windows is not support ***
```
# how to use
```php
require_once './EventLoop.php';

$loop = new EventLoop(); // first step is create an event loop instance

$loop->setTimeout(function() {}, 1000); // callback function executed after 1 second

$loop->setInterval(function() {}, 1000); // callback function executed every 1 second

$loop->setImmediate(function() {}); // callback function executed at the end of loop before going to next iteration 


HTTP::get("https://jsonplaceholder.typicode.com/todos/1", [])
    ->then(function ($response) {
        var_dump($response);
    })->catch(function ($err) {
        echo $err . PHP_EOL;
    }); // async http get request
HTTP::post("https://jsonplaceholder.typicode.com/posts", [
    "title" => "title number 1",
    "body" => "description"
])->then(function ($response) {
    var_dump($response);
})->catch(function ($err) {
    echo $err . PHP_EOL;
}); // async http post request
HTTP::put("https://jsonplaceholder.typicode.com/posts/1", [
    "id" => 1,
    "title" => "title number 1",
    "body" => "description"
])->then(function ($response) {
    var_dump($response);
})->catch(function ($err) {
    echo $err . PHP_EOL;
}); // async http put request
HTTP::delete("https://jsonplaceholder.typicode.com/posts/1", [])->then(function ($response) {
    echo "POST NUMBER 1 DELETED SUCCESS" . PHP_EOL;
})->catch(function ($err) {
    echo $err . PHP_EOL;
}); // async http delete request


$loop->readFileAsync("fileName.txt")
    ->then(function($data) { echo $data . PHP_EOL; })
    ->catch(function($err) { echo $err . PHP_EOL; }); // async read file (promise based)

$loop->writeFileAsync("fileName.txt", "data that should be write to file")
    ->then(function ($data) { echo $data . PHP_EOL; })
    ->catch(function ($err) { echo $err . PHP_EOL; }); // async write to file (promise based)

$loop->appendFileAsync("fileName.txt", "data that should be append to file")
    ->then(function ($data) { echo $data . PHP_EOL; })
    ->catch(function ($err) { echo $err . PHP_EOL; }); // async append to file 

$loop->deleteFIleAsync("fileName.txt")
    ->then(function ($data) { echo $data . PHP_EOL; })
    ->catch(function ($err) { echo $err . PHP_EOL; }); // async delete file

$loop->createServer("tcp://127.0.0.1:8080")
    ->then(function ($ip) { echo 'Server Started at port : ' . $ip . PHP_EOL;})
    ->catch(function ($err) {echo $err . PHP_EOL; }); // async tcp/ip server

//async await style (like javascript async/await)
Async::run(function () use (&$loop) {
    yield Async::delay(2000);
    echo 'async/await after two second' . PHP_EOL;
    yield Async::delay(3000);
    echo 'async/await after five second' . PHP_EOL;
    try {
        $readFile = yield $loop->readFileAsync("notExist.txt"); // will be thrown an error
        echo $readFile . " => async / await" . PHP_EOL; //  not being executed
    } catch (Error $err) {
        echo $err->getMessage() . " async / await " . PHP_EOL;
    }
    yield Async::delay(5000);
    return 'async/await style final delay' . PHP_EOL;
});

$loop->run(); // start the event loop
```
# Example server and client
**first start the server**
```php
php server.php
```
**then run client script as many as you want**
```php
php client.php
```
# features
    setInterval
    setTimeout
    setImmediate
    tcp/ip server
    async file write
    async file read
    async file append
    async file delete
    async http GET|POST|PUT|DELETE request