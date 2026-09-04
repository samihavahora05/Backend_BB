# 🛡️ BLUEBOXX SAFE DATA TRANSFER & PRODUCTION DEPLOYMENT GUIDE

This document establishes the **authoritative single source of truth**, safe data synchronization protocols, and non-destructive live deployment rules for the Blueboxx platform.

---

## 1. 🎯 PRIMARY ARCHITECTURE & SOURCE OF TRUTH PRINCIPLE

The database is the **sole authoritative source of truth** for all dynamic business and platform records.

```
┌────────────────────────────────────────────────────────┐
│                   DATABASE (Authoritative)              │
│       - Verified Experts (7 active approved)          │
│       - CMS Partner Companies (57 approved)            │
│       - CMS Colleges & Universities (32 approved)      │
│       - Student Job Offers (45 showcase entries)       │
│       - LMS Courses, Categories, Levels & Lessons      │
│       - Platform & System Configuration Settings       │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│                   BACKEND REST API                     │
│         (Laravel 11 / Sanctum / SQLite & MySQL)        │
│   - Provides clean JSON responses without mock caches  │
│   - Enforces foreign key integrity and auth gates      │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│                   FRONTEND UI (Next.js)                │
│   - Consumes live API endpoints via Axios / SWR        │
│   - Displays "No data found" or live DB state          │
│   - No hardcoded demo fallback arrays in production    │
└────────────────────────────────────────────────────────┘
```

---

## 2. 🚨 CRITICAL RULE — FORBIDDEN DESTRUCTIVE COMMANDS ON PRODUCTION

The following commands are **STRICTLY PROHIBITED** on any live, staging, or production server containing business data:

| ❌ NEVER RUN ON PRODUCTION | Reason / Hazard | Safe Alternative |
|---|---|---|
| `php artisan migrate:fresh` | Drops ALL database tables and destroys existing production data | `php artisan migrate` |
| `php artisan db:wipe` | Deletes all schemas, tables, views, and stored data | `php artisan app:sync-baseline-data` |
| `php artisan migrate:refresh` | Rolls back every migration and re-runs them | `php artisan migrate` |
| `DROP DATABASE` / `TRUNCATE` | Unrecoverable loss of customer, student, and transaction records | Scoped model deletes with audit logs |
| Blind database file overwriting | Destroys production user accounts, orders, and certificates | Idempotent baseline synchronization |

---

## 3. 📋 INVENTORIED BASELINE DATA (APPROVED PRODUCTION DATASET)

| Module / Table | Approved Count | Stable Identity Key | Behavior Upon Sync |
|---|---|---|---|
| **Verified Instructors / Experts** (`users`, `expert_profiles`) | 7 Active | `email` | `updateOrCreate` on email; maintains verified status |
| **CMS Companies** (`cms_companies`) | 57 Verified | `slug` / `name` | `updateOrCreate` on slug; updates logos and industries |
| **CMS Colleges** (`cms_colleges`) | 32 Colleges | `slug` / `name` | `updateOrCreate` on slug; updates logos and location |
| **Student Job Offers** (`student_job_offers`) | 45 Offers | `student_name` + `company_name` | Preserved and served via `/api/public/cms/job-offers` |
| **LMS Categories & Levels** (`course_categories`, `course_levels`) | Core standard | `slug` | `updateOrCreate` on category slug |
| **Global Platform Settings** (`global_settings`, `system_settings`) | Site defaults | `key` | `updateOrCreate` on setting key |

---

## 4. 💾 BACKUP & PRE-DEPLOYMENT SAFETY PROTOCOL

Before applying any migration or baseline sync to the live server, perform a verified backup:

### MySQL / MariaDB Live Server Backup:
```bash
# 1. Create timestamped database backup
mysqldump -u [DB_USER] -p[DB_PASSWORD] [DB_DATABASE] > /safe_backups/blueboxx_db_backup_$(date +%F_%H%M%S).sql

# 2. Verify backup file size and integrity
ls -lh /safe_backups/blueboxx_db_backup_*.sql
head -n 25 /safe_backups/blueboxx_db_backup_*.sql
```

### SQLite Live Server Backup:
```bash
# 1. Create hot copy of SQLite database
cp database/database.sqlite /safe_backups/database_backup_$(date +%F_%H%M%S).sqlite

# 2. Verify SQLite integrity
sqlite3 database/database.sqlite "PRAGMA integrity_check;"
```

---

## 5. 🔍 DRY RUN SIMULATION BEFORE LIVE COMMIT

To inspect what will be added or updated without writing a single byte to the database, run:

```bash
php artisan app:sync-baseline-data --dry-run
```

**Expected Dry Run Output:**
```text
================================================================
 🛡️  BLUEBOXX SAFE BASELINE DATA SYNCHRONIZATION
 Mode: 🔍 DRY RUN (Simulation Only - No changes committed)
================================================================

+------------------------------------+----------------+---------------------+---------------------+----------------------+
| Entity                             | Total Baseline | Created / To Create | Updated / To Update | Status               |
+------------------------------------+----------------+---------------------+---------------------+----------------------+
| Roles & Permissions                | 8              | 0                   | 8                   | OK                   |
| Platform Settings                  | 9              | 0                   | 9                   | OK                   |
| CMS Industries                     | 12             | 0                   | 12                  | OK                   |
| CMS Companies                      | 43             | 0                   | 43                  | OK                   |
| CMS Colleges                       | 8              | 0                   | 8                   | OK                   |
| Student Job Offers (CMS Showcase)  | 45             | 0                   | 45                  | OK (45 Active Offers)|
| LMS Categories & Levels            | 8              | 0                   | 8                   | OK                   |
| Verified Active Experts            | 7              | 0                   | 7                   | OK (7 Active Experts)|
+------------------------------------+----------------+---------------------+---------------------+----------------------+

✅ DRY RUN complete. 0 records were modified.
Run "php artisan app:sync-baseline-data" to apply these changes safely.
```

---

## 6. 🚀 LIVE DEPLOYMENT SEQUENCE (STEP-BY-STEP)

When pulling changes to the live or mentor environment, follow this non-destructive deployment workflow:

```bash
# Step 1: Pull the latest Git codebase
git pull origin main

# Step 2: Install / Update PHP dependencies (Optimized for production)
composer install --no-dev --optimize-autoloader

# Step 3: Run incremental migrations (Non-destructive)
php artisan migrate --force

# Step 4: Run the safe baseline synchronization
php artisan app:sync-baseline-data --force

# Step 5: Ensure storage symlink exists
php artisan storage:link

# Step 6: Optimize route, config, and view caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 7: Restart PHP-FPM / queue workers if applicable
# sudo systemctl reload php8.2-fpm
# php artisan queue:restart
```

---

## 7. 📁 STATIC ASSETS & STORAGE FILE VERIFICATION

- All core company logos, partner badges, and static icons are stored in `public/logo/` and tracked in Git.
- Uploaded avatars, thumbnails, and PDFs are served through `/storage/` via the `StorageHelper::url()` utility.
- Running `php artisan storage:link` connects `public/storage` to `storage/app/public`.

---

## 8. 🔐 SECRETS & ENVIRONMENT ISOLATION

- **Never commit `.env` files to Git.**
- All required environment keys are defined in `.env.example`.
- Ensure production `.env` specifies:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `CORS_ALLOWED_ORIGINS=https://blueboxx.in,https://www.blueboxx.in`
  - `FRONTEND_URL=https://blueboxx.in`
  - `NEXT_PUBLIC_API_URL=https://backend.blueboxx.in/api`

---

## 9. 🔙 EMERGENCY ROLLBACK PROCEDURE

If unexpected discrepancies occur:
1. **Stop Application Traffic**:
   ```bash
   php artisan down --secret="blueboxx-maintenance-key"
   ```
2. **Restore Database from Verified Backup**:
   ```bash
   # MySQL:
   mysql -u [DB_USER] -p[DB_PASSWORD] [DB_DATABASE] < /safe_backups/blueboxx_db_backup_[TIMESTAMP].sql
   
   # SQLite:
   cp /safe_backups/database_backup_[TIMESTAMP].sqlite database/database.sqlite
   ```
3. **Clear Caches**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```
4. **Bring Application Back Online**:
   ```bash
   php artisan up
   ```

---

## 10. ✅ ACCEPTANCE VERIFICATION CHECKLIST

- [x] Database is authoritative single source of truth across LMS, CMS, and Expert modules.
- [x] Deleted records (e.g., Vikram Verma) are permanently removed and excluded from seeders.
- [x] `app:sync-baseline-data` provides dry-run diffing and safe non-destructive live sync.
- [x] `migrate:fresh` and `db:wipe` are banned from production workflows.
- [x] All 43+ CMS companies and 8+ CMS colleges load correctly with valid logos.
- [x] 45 student job offers are served dynamically from `/api/public/cms/job-offers`.
- [x] Build passes with 0 errors (`npm run build`).
- [x] No secrets, passwords, or production credentials are leaked in Git.
