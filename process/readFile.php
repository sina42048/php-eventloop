<?php
stream_set_blocking(STDOUT, false);
$myfile = fopen($argv[1], "r");
while (!feof($myfile)) {
    $data = stream_get_contents($myfile, 1024 * 1024);
    fwrite(STDOUT, $data);
    usleep(10000);
}
fclose($myfile);
