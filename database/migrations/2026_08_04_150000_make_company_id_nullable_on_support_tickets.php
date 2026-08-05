<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement("CREATE TABLE IF NOT EXISTS support_tickets_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                ticket_number VARCHAR NOT NULL,
                company_id INTEGER NULL,
                user_id INTEGER NULL,
                subject VARCHAR NOT NULL,
                description TEXT NOT NULL,
                priority VARCHAR NOT NULL DEFAULT 'Normal',
                status VARCHAR NOT NULL DEFAULT 'Open',
                assigned_admin_id INTEGER NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE NO ACTION,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE NO ACTION,
                FOREIGN KEY(company_id) REFERENCES company_profiles(id) ON DELETE CASCADE ON UPDATE NO ACTION
            )");
            DB::statement('INSERT INTO support_tickets_new SELECT * FROM support_tickets');
            DB::statement('DROP TABLE support_tickets');
            DB::statement('ALTER TABLE support_tickets_new RENAME TO support_tickets');
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement("CREATE TABLE IF NOT EXISTS support_tickets_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                ticket_number VARCHAR NOT NULL,
                company_id INTEGER NOT NULL,
                user_id INTEGER NULL,
                subject VARCHAR NOT NULL,
                description TEXT NOT NULL,
                priority VARCHAR NOT NULL DEFAULT 'Normal',
                status VARCHAR NOT NULL DEFAULT 'Open',
                assigned_admin_id INTEGER NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE NO ACTION,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE NO ACTION,
                FOREIGN KEY(company_id) REFERENCES company_profiles(id) ON DELETE CASCADE ON UPDATE NO ACTION
            )");
            DB::statement('INSERT INTO support_tickets_old SELECT * FROM support_tickets WHERE company_id IS NOT NULL');
            DB::statement('DROP TABLE support_tickets');
            DB::statement('ALTER TABLE support_tickets_old RENAME TO support_tickets');
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable(false)->change();
            });
        }
    }
};
