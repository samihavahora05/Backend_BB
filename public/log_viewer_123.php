<?php
$lines = file('../storage/logs/laravel.log');
$last100 = array_slice($lines, -100);
echo implode("", $last100);
