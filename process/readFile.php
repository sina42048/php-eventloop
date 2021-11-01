<?php

$myfile = fopen($argv[1], "r");
echo fread($myfile, filesize($argv[1]));
fclose($myfile);
