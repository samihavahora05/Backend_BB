<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=blueboxx_db', 'root', '');
$stmt = $pdo->query('SELECT * FROM internship_applications');
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($apps) . "\n";
print_r($apps);
