<?php
// Since we don't have terminal access easily, we can just delete the cache file.
$cache_file = __DIR__ . '/../bootstrap/cache/routes-v7.php';
if (file_exists($cache_file)) {
    unlink($cache_file);
    echo "Deleted routes-v7.php\n";
} else {
    echo "routes-v7.php not found\n";
}

// Or run artisan command
$cwd = __DIR__ . '/..';
exec("cd /D \"$cwd\" && php artisan route:clear 2>&1", $out);
echo implode("\n", $out);
