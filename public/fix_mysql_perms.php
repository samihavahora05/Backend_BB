<?php
$data = 'D:\\xampp\\mysql\\data';
// Try icacls to grant full control to Everyone
exec("icacls \"$data\" /grant Everyone:(OI)(CI)F /T", $out, $code);
echo "Icacls code: $code\n";
echo implode("\n", $out) . "\n";

// Remove read-only again just in case
exec("attrib -R \"$data\\*.*\" /S", $out2, $code2);
echo "Attrib code: $code2\n";

// Start MySQL
$mysqld = 'D:\\xampp\\mysql\\bin\\mysqld.exe';
$ini = 'D:\\xampp\\mysql\\bin\\my.ini';
pclose(popen('start /B "" "' . $mysqld . '" --defaults-file="' . $ini . '"', 'r'));
echo "Started mysqld.\n";
