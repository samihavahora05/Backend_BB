<?php
$dir = new RecursiveDirectoryIterator(__DIR__.'/../app/Http');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$errors = [];
chdir(__DIR__.'/../app/Http');
foreach($files as $file) {
    $path = $file[0];
    $relativePath = str_replace(str_replace('\\', '/', realpath(__DIR__.'/../app/Http')) . '/', '', str_replace('\\', '/', realpath($path)));
    exec("php -l \"" . $relativePath . "\" 2>&1", $output, $return);
    if ($return !== 0) {
        $errors[] = [
            'file' => $relativePath,
            'error' => implode("\n", $output)
        ];
    }
    $output = []; // Reset output array!
}

header('Content-Type: application/json');
echo json_encode($errors, JSON_PRETTY_PRINT);
