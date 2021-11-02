<?php
$myfile = fopen($argv[1], "r");
fwrite(STDOUT, stream_get_contents($myfile, filesize($argv[1])));
usleep(10000);
fclose($myfile);
