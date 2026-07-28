<?php
$mysqld = 'D:\\xampp\\mysql\\bin\\mysqld.exe';
$ini = 'D:\\xampp\\mysql\\bin\\my.ini';
$cmd = "\"$mysqld\" --defaults-file=\"$ini\"";

echo "Running: $cmd\n";
$descriptorspec = [
   0 => ["pipe", "r"],  // stdin
   1 => ["pipe", "w"],  // stdout
   2 => ["pipe", "w"]   // stderr
];

$process = proc_open($cmd, $descriptorspec, $pipes, 'D:\\xampp\\mysql\\bin');
if (is_resource($process)) {
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    echo "Started process.\n";
    sleep(2);
    echo "Stdout: " . stream_get_contents($pipes[1]) . "\n";
    echo "Stderr: " . stream_get_contents($pipes[2]) . "\n";
    
    // Do not close it! We want it to keep running!
    // But we will just exit the PHP script and hopefully it stays running.
}
