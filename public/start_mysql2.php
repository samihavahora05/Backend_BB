<?php
$bat_path = 'D:\\xampp\\mysql_start.bat';
if (file_exists($bat_path)) {
    echo "Found bat path.\n";
    pclose(popen('start /B "" "' . $bat_path . '"', 'r'));
    echo "Started via bat.\n";
} else {
    // try to start it properly with defaults-file
    $ini = 'D:\\xampp\\mysql\\bin\\my.ini';
    $mysqld = 'D:\\xampp\\mysql\\bin\\mysqld.exe';
    if (file_exists($ini)) {
        pclose(popen('start /B "" "' . $mysqld . '" --defaults-file="' . $ini . '"', 'r'));
        echo "Started with my.ini\n";
    } else {
        echo "Could not find my.ini\n";
    }
}
