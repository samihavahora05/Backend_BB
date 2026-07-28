<?php
$file = 'D:\\xampp\\mysql\\data\\ibdata1';
$fp = @fopen($file, 'a');
if ($fp) {
    echo "Successfully opened ibdata1 for writing.\n";
    fclose($fp);
} else {
    echo "Failed to open ibdata1 for writing.\n";
    $error = error_get_last();
    print_r($error);
}
