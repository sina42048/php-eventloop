<?php
error_reporting(0);

$myFile = fopen($argv[1], "w") or die("-2");
$txt = $argv[2];
fwrite($myFile, $txt);
fwrite(STDOUT, "File Write success");
fclose($myFile);
