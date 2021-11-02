<?php
require_once './timers/timers.php';
require_once './process/File.php';

File::writeFileAsync("test.txt", "hello from test.txt file")->then(function ($data) {
    echo $data . PHP_EOL;
});

setTimeout(function () {
    File::readFileAsync("test.txt")->then(function ($data) {
        echo $data . PHP_EOL;
    });
}, 2000);

$socket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
stream_set_blocking($socket, false);

$connections = [];
$write_holder = [];
$read = [];
$write = [];
$except = null;
$messageQueue = [];


while (true) {
    $read = $connections;
    $read[] = $socket;
    $write = $write_holder;
    foreach (File::$pipes_holder as &$pipe) {
        $read[] = $pipe['resource'];
    }

    $currentTime = hrtime(true);
    $delayNextLoop = null;
    foreach ($timers as $key => &$timer) {
        if ($timer['happend'] === false) {
            if ($delayNextLoop === null) {
                $delayNextLoop = $timer['time'] - $currentTime < 0 ? 0 : $timer['time'] - $currentTime;
            }
            $delayNextLoop = ($timer['time'] - $currentTime) < $delayNextLoop ? (($timer['time'] - $currentTime) < 0 ? 0 : $timer['time'] - $currentTime) : $delayNextLoop;
        }

        if ($timer['time'] <= hrtime(true) && $timer['happend'] === false) {
            switch ($timer['type']) {
                case 'interval':
                    $timer['time'] = hrtime(true) + $timer['delay'];
                    call_user_func($timer['callback']);
                    break;
                case 'timeout':
                    $timer['happend'] = true;
                    call_user_func($timer['callback']);
                    break;
            }
        }
    }

    if ($delayNextLoop !== null) {
        $second = $delayNextLoop > 999999999 ? (round($delayNextLoop / 1000000000) == 0 ? 1 : round($delayNextLoop / 1000000000)) : 0;
        $miliSecond = $delayNextLoop / 100000 > 999999999 ? 0 : $delayNextLoop / 100000;
        $delayNextLoop = [$second, $miliSecond];
    } else {
        $delayNextLoop = [0, 200000];
    }

    if (stream_select($read, $write, $except, $delayNextLoop[0], $delayNextLoop[1])) {
        foreach ($write as &$w) {
            $peer = stream_socket_get_name($w, true);
            foreach ($messageQueue as $k => &$messages) {
                foreach ($messages as $key => &$msg) {
                    $written = fwrite($w, $msg);
                    if ($written === strlen($msg)) {
                        unset($messageQueue[$peer][$key]);
                        if (empty($messageQueue[$peer])) {
                            unset($write_holder[$peer]);
                            unset($messageQueue[$peer]);
                        }
                    } else {
                        $messageQueue[$peer][$key] = substr($msg, $written);
                    }
                }
            }
        }

        foreach ($read as &$r) {
            if (array_key_exists((int)$r, File::$pipes_holder)) {
                if (feof($r)) {
                    call_user_func(File::$pipes_holder[(int)$r]['resolve'], File::$pipes_holder[(int)$r]['data']);
                    $pipes_holder[(int)$r]['data'] = '';
                    pclose(File::$pipes_holder[(int)$r]['resource']);
                    unset(File::$pipes_holder[(int)$r]);
                } else {
                    File::$pipes_holder[(int)$r]['data'] .= stream_get_contents($r);
                }
            } else {
                if ($c = @stream_socket_accept($r, 0, $peer)) {
                    stream_set_blocking($c, 0);
                    $connections[$peer] = $c;
                    echo $peer . ' Connected' . PHP_EOL;
                    $write_holder[$peer] = $connections[$peer];
                    $messageQueue[$peer][] = "Hello user " . $peer;
                } else {
                    $peer = stream_socket_get_name($r, true);
                    if (feof($r)) {
                        echo 'Connection closed ' . $peer . PHP_EOL;
                        unset($connections[$peer]);
                        unset($write_holder[$peer]);
                        unset($messageQueue[$peer]);
                        fclose($r);
                    } else {
                        $contents = fread($r, 1024);
                        if ($contents) {
                            echo "Client $peer said $contents" . PHP_EOL;
                            $messageQueue[$peer][] = "$contents recieved ! :D";
                            $write_holder[$peer] = $connections[$peer];
                        }
                    }
                }
            }
        }
    }

    foreach ($futureTicks as $key => &$future) {
        call_user_func($future['callback']);
        unset($futureTicks[$key]);
    }
}
