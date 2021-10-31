<?php
$myFile = fopen($argv[1], "w");
$txt = $argv[2];
fwrite($myFile, $txt);
echo 'File Write success';
fclose($myFile);
