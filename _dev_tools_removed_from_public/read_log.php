<?php
$file = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($file)) {
    $lines = file($file);
    $last_lines = array_slice($lines, -1000);
    foreach ($last_lines as $line) {
        if (strpos($line, 'UnauthorizedException') !== false || strpos($line, 'GuardDoesNotMatch') !== false) {
            echo $line;
        }
    }
}
