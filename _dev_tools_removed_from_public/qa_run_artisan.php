<?php
chdir(__DIR__.'/../');
exec("php -r \"echo count(file('app/Http/Controllers/Api/Admin/AdminInternshipController.php'));\" 2>&1", $output, $return);
header('Content-Type: text/plain');
echo implode("\n", $output);
