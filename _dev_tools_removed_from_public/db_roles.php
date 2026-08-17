<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=blueboxx_db', 'root', '');
$stmt = $pdo->prepare("SELECT r.name, r.guard_name FROM model_has_roles mhr JOIN roles r ON mhr.role_id = r.id WHERE mhr.model_id = 1");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "User 1 roles:\n";
print_r($roles);
