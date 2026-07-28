<?php
$data = 'D:\\xampp\\mysql\\data';
exec("attrib -R \"$data\\*.*\" /S", $out, $code);
echo "Attrib code: $code\n";
echo "Removed read-only attributes.\n";

$mysqld = 'D:\\xampp\\mysql\\bin\\mysqld.exe';
$ini = 'D:\\xampp\\mysql\\bin\\my.ini';
pclose(popen('start /B "" "' . $mysqld . '" --defaults-file="' . $ini . '"', 'r'));
echo "Started mysqld again.\n";
