<?php
exec('tasklist /FI "IMAGENAME eq mysqld.exe"', $output);
echo implode("\n", $output);
