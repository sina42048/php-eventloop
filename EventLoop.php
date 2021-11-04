<?php
require_once './timer/Timer.php';
require_once './promise/Promise.php';
require_once './process/File.php';
require_once './async_await/Async.php';

class EventLoop
{
    private $socket;
    private $connections = [];
    private $write_holder = [];
    private $read = [];
    private $write = [];
    private $except = null;
    private $messageQueue = [];

    public function createServer($ipAddress)
    {
        $promise = new Promise(function ($resolve, $reject) use ($ipAddress) {
            $this->socket = stream_socket_server($ipAddress, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
            if ($this->socket) {
                $resolve($ipAddress);
            } else {
                $reject("failed to open server");
            }
            stream_set_blocking($this->socket, false);
        });
        return $promise;
    }

    public function setTimeout($callback, $delay)
    {
        return Timer::setTimeout($callback, $delay);
    }

    public function setInterval($callback, $delay)
    {
        return Timer::setInterval($callback, $delay);
    }

    public function setImmediate($callback)
    {
        return Timer::setImmediate($callback);
    }

    public function clearInterval($id)
    {
        return Timer::clearInterval($id);
    }

    public function clearTimeout($id)
    {
        return Timer::clearTimeout($id);
    }

    public function readFileAsync($fileName)
    {
        return File::readFileAsync($fileName);
    }

    public function writeFileAsync($fileName, $text)
    {
        return File::writeFileAsync($fileName, $text);
    }

    public function run()
    {
        while (true) {
            $this->read = $this->connections;
            $this->read[] = $this->socket;
            $this->write = $this->write_holder;
            foreach (File::$pipes_holder as &$pipe) {
                $this->read[] = $pipe['resource'];
            }

            $currentTime = hrtime(true);
            $delayNextLoop = null;
            foreach (Timer::$timers as $key => &$timer) {
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

            if (stream_select($this->read, $this->write, $this->except, $delayNextLoop[0], $delayNextLoop[1])) {
                foreach ($this->write as &$w) {
                    $peer = stream_socket_get_name($w, true);
                    foreach ($this->messageQueue as &$messages) {
                        foreach ($messages as $key => &$msg) {
                            $written = fwrite($w, $msg);
                            if ($written === strlen($msg)) {
                                unset($this->messageQueue[$peer][$key]);
                                if (empty($this->messageQueue[$peer])) {
                                    unset($this->write_holder[$peer]);
                                    unset($this->messageQueue[$peer]);
                                }
                            } else {
                                $this->messageQueue[$peer][$key] = substr($msg, $written);
                            }
                        }
                    }
                }

                foreach ($this->read as &$r) {
                    if (array_key_exists((int)$r, File::$pipes_holder)) {
                        if (feof($r)) {
                            if (File::$pipes_holder[(int)$r]['data'] === '-1') {
                                call_user_func(File::$pipes_holder[(int)$r]['reject'], 'file not found');
                            } else if (File::$pipes_holder[(int)$r]['data'] === '-2') {
                                call_user_func(File::$pipes_holder[(int)$r]['reject'], 'write file failed');
                            } else {
                                call_user_func(File::$pipes_holder[(int)$r]['resolve'], File::$pipes_holder[(int)$r]['data']);
                            }
                            File::$pipes_holder[(int)$r]['data'] = '';
                            pclose(File::$pipes_holder[(int)$r]['resource']);
                            unset(File::$pipes_holder[(int)$r]);
                        } else {
                            File::$pipes_holder[(int)$r]['data'] .= stream_get_contents($r, 64 * 64);
                        }
                    } else {
                        if ($c = @stream_socket_accept($r, 0, $peer)) {
                            stream_set_blocking($c, 0);
                            $this->connections[$peer] = $c;
                            echo $peer . ' Connected' . PHP_EOL;
                            $this->write_holder[$peer] = $this->connections[$peer];
                            $this->messageQueue[$peer][] = "Hello user " . $peer;
                        } else {
                            $peer = stream_socket_get_name($r, true);
                            if (feof($r)) {
                                echo 'Connection closed ' . $peer . PHP_EOL;
                                unset($this->connections[$peer]);
                                unset($this->write_holder[$peer]);
                                unset($this->messageQueue[$peer]);
                                fclose($r);
                            } else {
                                $contents = fread($r, 1024);
                                if ($contents) {
                                    echo "Client $peer said $contents" . PHP_EOL;
                                    $this->messageQueue[$peer][] = "$contents recieved ! :D";
                                    $this->write_holder[$peer] = $this->connections[$peer];
                                }
                            }
                        }
                    }
                }
            }

            foreach (Timer::$futureTicks as $key => &$future) {
                call_user_func($future['callback']);
                unset(Timer::$futureTicks[$key]);
            }
        }
    }
}
