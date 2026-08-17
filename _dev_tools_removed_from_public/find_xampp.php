<?php
exec('wmic process get processid,name,executablepath | findstr /i xampp', $output);
echo "XAMPP processes:\n";
echo implode("\n", $output);

exec('wmic process get processid,name | findstr /i mysql', $out2);
echo "\nMySQL processes:\n";
echo implode("\n", $out2);

exec('wmic process get processid,name | findstr /i maria', $out3);
echo "\nMariaDB processes:\n";
echo implode("\n", $out3);
