<?php
stream_set_blocking(STDOUT, false);
$myFile = fopen($argv[1], "w");
$txt = $argv[2];
fwrite($myFile, $txt);
fwrite(STDOUT, "File Write success");
fclose($myFile);
