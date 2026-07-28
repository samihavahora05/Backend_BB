<?php
chdir(__DIR__.'/../');
exec("php artisan route:list 2>&1", $output, $return);
header('Content-Type: text/plain');
echo implode("\n", $output);
