<?php
$ports = [3306, 3307, 3308, 33060];
foreach ($ports as $port) {
    echo "Trying port $port...\n";
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=$port", "root", "");
        echo "Success on port $port!\n";
    } catch (Exception $e) {
        echo "Failed on port $port: " . $e->getMessage() . "\n";
    }
}
