<?php
$routes = shell_exec('php artisan route:list --path=api/public/jobs');
echo $routes;
