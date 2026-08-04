<?php
/**
 * Quick fix script: makes support_tickets.company_id nullable in SQLite
 * Run from: d:\blueboxx\blueboxx web\backend
 * Usage: php fix_support_tickets.php
 */

$dbPath = __DIR__ . '/database/database.sqlite';
$db = new SQLite3($dbPath);

echo "Fixing support_tickets table...\n";

$db->exec('PRAGMA foreign_keys=OFF');

$db->exec('BEGIN TRANSACTION');

try {
    $db->exec('CREATE TABLE IF NOT EXISTS support_tickets_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        ticket_number VARCHAR NOT NULL,
        company_id INTEGER NULL,
        user_id INTEGER NULL,
        subject VARCHAR NOT NULL,
        description TEXT NOT NULL,
        priority VARCHAR NOT NULL DEFAULT \'Normal\',
        status VARCHAR NOT NULL DEFAULT \'Open\',
        assigned_admin_id INTEGER NULL,
        created_at DATETIME,
        updated_at DATETIME
    )');

    $db->exec('INSERT INTO support_tickets_new SELECT * FROM support_tickets');
    $db->exec('DROP TABLE support_tickets');
    $db->exec('ALTER TABLE support_tickets_new RENAME TO support_tickets');

    $db->exec('COMMIT');
    echo "✅ Done! company_id is now nullable in support_tickets.\n";
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$db->exec('PRAGMA foreign_keys=ON');
$db->close();
