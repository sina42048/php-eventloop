<?php
error_reporting(0);

$myfile = fopen($argv[1], "r") or die("-1");
fwrite(STDOUT, stream_get_contents($myfile, filesize($argv[1])));
usleep(10000);
fclose($myfile);
