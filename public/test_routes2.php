<?php
// Since artisan didn't work from public dir, let's just run it from the root backend dir
$cwd = 'D:\\blueboxx\\blueboxx web\\backend';
$cmd = 'php artisan route:list --path=api/public/jobs';
exec("cd /D \"$cwd\" && $cmd", $out);
echo implode("\n", $out);
