<?php
require_once './bootstrap.php';

class EventLoop
{
    private $socket = [];
    private $connections = [];
    private $write_holder = [];
    private $read = [];
    private $write = [];
    private $except = null;
    private $messageQueue = [];

    public function createServer($ipAddress)
    {
        $promise = new Promise(function ($resolve, $reject) use ($ipAddress) {
            $socket = stream_socket_server($ipAddress, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
            if ($socket) {
                $resolve($ipAddress);
                stream_set_blocking($socket, false);
                $this->socket[] = $socket;
            } else {
                $reject("failed to open server");
            }
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

    public function appendFileAsync($fileName, $text)
    {
        return File::appendFileAsync($fileName, $text);
    }

    public function deleteFileAsync($fileName)
    {
        return File::deleteFIleAsync($fileName);
    }

    public function run()
    {
        $reader_write_pipe = fopen("/tmp/pipe", "w");
        $reader_read_pipe = fopen("/tmp/pipe_main", "r");

        stream_set_blocking($reader_read_pipe, false);

        File::$communicationPipes[(int)$reader_write_pipe] = $reader_write_pipe;
        File::$communicationPipes[(int)$reader_read_pipe] = $reader_read_pipe;

        while (true) {
            $this->read = $this->connections;
            if (count($this->socket)) {
                foreach ($this->socket as $socket) {
                    $this->read[] = $socket;
                }
            }
            $this->read[] = File::$communicationPipes[(int)$reader_read_pipe];
            $this->write = $this->write_holder;

            if (count($this->write) || count($this->read) || count(Timer::$timers) || count(Timer::$futureTicks)) {
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


                if (count($this->read) || count($this->write)) {
                    if (@stream_select($this->read, $this->write, $this->except, $delayNextLoop[0], $delayNextLoop[1])) {
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
                            if (array_key_exists((int)$r, File::$communicationPipes)) {
                                $message = fgets($r);
                                $message = explode("_+_", $message);
                                $randomNumber = (int)$message[3];
                                $fileSize = isset($message[4]) ? (int)$message[4] : null;
                                $message = trim($message[0]) . "_" . trim($message[1]);

                                switch ($message) {
                                    case 'ERR_NOTFOUND':
                                        call_user_func(File::$operations_holder[(int)$randomNumber]['err'], "file not found.");
                                        break;
                                    case 'READ_SUCCESS':
                                        $shm_id = shmop_open($randomNumber, "c", 0644, $fileSize);
                                        call_user_func(File::$operations_holder[(int)$randomNumber]['callback'], shmop_read($shm_id, 0, 0));
                                        shmop_delete($shm_id);
                                        shmop_close($shm_id);
                                        unset(File::$operations_holder[(int)$randomNumber]);
                                        break;
                                    case 'WRITE_SUCCESS':
                                        call_user_func(File::$operations_holder[(int)$randomNumber]['callback'], "file write success");
                                        unset(File::$operations_holder[(int)$randomNumber]);
                                        break;
                                    case 'APPEND_SUCCESS':
                                        call_user_func(File::$operations_holder[(int)$randomNumber]['callback'], "file append success");
                                        unset(File::$operations_holder[(int)$randomNumber]);
                                        break;
                                    case 'DELETE_SUCCESS':
                                        call_user_func(File::$operations_holder[(int)$randomNumber]['callback'], "file delete success");
                                        unset(File::$operations_holder[(int)$randomNumber]);
                                        break;
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
                } else {
                    usleep(5000);
                }

                foreach (Timer::$futureTicks as $key => &$future) {
                    call_user_func($future['callback']);
                    unset(Timer::$futureTicks[$key]);
                }
            } else {
                exit(0);
            }
        }
    }
}
