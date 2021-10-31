<?php

$fp = fsockopen('127.0.0.1', 8080, $errno, $errstr, 30);
stream_set_blocking($fp, false);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    while (true) {
        if ($c = fread($fp, 1024)) {
            echo $c . PHP_EOL;
            echo 'say something : ';
            $message = readline();
            fwrite($fp, $message);
        }
        usleep(10000);
    }
}
