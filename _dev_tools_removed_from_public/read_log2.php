<?php
$file = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($file)) {
    $lines = file($file);
    $last_lines = array_slice($lines, -200);
    foreach ($last_lines as $line) {
        if (strpos($line, 'local.ERROR') !== false || strpos($line, 'Exception') !== false || strpos($line, 'SQLSTATE') !== false) {
            echo $line;
        }
    }
}
