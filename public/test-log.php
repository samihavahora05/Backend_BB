<?php
$file = 'd:/blueboxx/blueboxx web/backend/storage/logs/laravel.log';
$fp = fopen($file, 'r');
fseek($fp, -10000, SEEK_END);
$content = fread($fp, 10000);
fclose($fp);
echo "<pre>";
echo htmlspecialchars($content);
echo "</pre>";
