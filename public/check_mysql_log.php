<?php
$data_dir = 'D:\\xampp\\mysql\\data';
if (is_dir($data_dir)) {
    $files = scandir($data_dir);
    foreach ($files as $file) {
        if (substr($file, -4) === '.err') {
            echo "Error log: $file\n";
            $content = file_get_contents($data_dir . '\\' . $file);
            // print last 20 lines
            $lines = explode("\n", $content);
            echo implode("\n", array_slice($lines, -20));
        }
    }
}
