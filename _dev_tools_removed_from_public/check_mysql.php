<?php
exec('tasklist', $output);
$mysql_running = false;
foreach ($output as $line) {
    if (stripos($line, 'mysql') !== false || stripos($line, 'mariadb') !== false) {
        $mysql_running = true;
        echo $line . "\n";
    }
}
if (!$mysql_running) {
    echo "MySQL is NOT running.\n";
    // Try to start it? Maybe it's a Laragon or XAMPP setup.
    echo "Starting MySQL via net start...\n";
    exec('net start mysql', $start_output);
    print_r($start_output);
}
