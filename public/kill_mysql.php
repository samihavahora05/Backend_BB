<?php
// force kill any mysqld.exe
exec('taskkill /F /IM mysqld.exe /T', $output, $code);
echo "Kill code: $code\n";
echo implode("\n", $output) . "\n";

// wait a moment
sleep(2);

// try to start it again
$mysqld = 'D:\\xampp\\mysql\\bin\\mysqld.exe';
$ini = 'D:\\xampp\\mysql\\bin\\my.ini';
pclose(popen('start /B "" "' . $mysqld . '" --defaults-file="' . $ini . '"', 'r'));
echo "Started mysqld again.\n";
