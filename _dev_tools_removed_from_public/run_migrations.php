<?php
chdir(__DIR__ . '/../');
echo shell_exec('php artisan migrate --force');
echo "\nStatus:\n";
echo shell_exec('php artisan migrate:status');
