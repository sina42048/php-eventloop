<?php
stream_set_blocking(STDOUT, false);
$myfile = fopen($argv[1], "r");
$data = fread($myfile, filesize($argv[1]));
fwrite(STDOUT, $data);
fclose($myfile);
