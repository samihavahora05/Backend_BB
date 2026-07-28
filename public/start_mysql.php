<?php
$drives = ['C:\\', 'D:\\'];
$mysql_path = '';

foreach ($drives as $drive) {
    if (!is_dir($drive)) continue;
    // We just check common paths
    $paths = [
        $drive . 'xampp\\mysql\\bin\\mysqld.exe',
        $drive . 'laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqld.exe',
        $drive . 'tools\\mysql\\bin\\mysqld.exe',
        $drive . 'Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqld.exe'
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            $mysql_path = $p;
            break 2;
        }
    }
}

if ($mysql_path) {
    echo "Found mysqld: " . $mysql_path . "\n";
    // Start it in background
    pclose(popen('start /B "" "' . $mysql_path . '" --console > NUL 2>&1', 'r'));
    echo "Started mysql.\n";
} else {
    echo "Could not find mysqld.exe in common paths.\n";
}
