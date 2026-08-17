<?php
$dirs = [
    __DIR__ . '/../../pages/college/cohorts',
    __DIR__ . '/../../pages/college/courses',
    __DIR__ . '/../../pages/college/enrollment',
    __DIR__ . '/../../pages/college/performance',
];

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') $dirPath .= '/';
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) deleteDir($file);
        else unlink($file);
    }
    rmdir($dirPath);
}

foreach ($dirs as $dir) {
    deleteDir($dir);
    echo "Deleted $dir\n";
}
