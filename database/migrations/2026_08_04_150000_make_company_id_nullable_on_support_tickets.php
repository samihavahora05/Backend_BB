<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN, so we recreate the table with nullable company_id
        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement('CREATE TABLE IF NOT EXISTS support_tickets_new (
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

        // Copy existing data
        DB::statement('INSERT INTO support_tickets_new SELECT * FROM support_tickets');

        // Drop old table and rename new one
        DB::statement('DROP TABLE support_tickets');
        DB::statement('ALTER TABLE support_tickets_new RENAME TO support_tickets');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        // Reverse: make company_id NOT NULL again (risky if data has NULLs)
        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement('CREATE TABLE IF NOT EXISTS support_tickets_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            ticket_number VARCHAR NOT NULL,
            company_id INTEGER NOT NULL,
            user_id INTEGER NULL,
            subject VARCHAR NOT NULL,
            description TEXT NOT NULL,
            priority VARCHAR NOT NULL DEFAULT \'Normal\',
            status VARCHAR NOT NULL DEFAULT \'Open\',
            assigned_admin_id INTEGER NULL,
            created_at DATETIME,
            updated_at DATETIME
        )');

        DB::statement('INSERT INTO support_tickets_old SELECT * FROM support_tickets WHERE company_id IS NOT NULL');
        DB::statement('DROP TABLE support_tickets');
        DB::statement('ALTER TABLE support_tickets_old RENAME TO support_tickets');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
