<?php
exec('wmic process get processid,name,executablepath | findstr /i mysql', $output, $code);
echo implode("\n", $output);
