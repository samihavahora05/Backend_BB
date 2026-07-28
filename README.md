# BlueBoxx DA Platform - Backend

This is the central Laravel 12 backend for the BlueBoxx DA Platform. It powers the E-Learning Engine, Career ATS, Enterprise Dashboards, CMS, and unified authentication.

## Requirements
- PHP 8.2+
- Composer 2.x
- Node.js 18+ (for frontend assets if needed)
- MySQL 8.0+ (Production)
- SQLite (Testing)
- Redis (Optional, for caching and queues)

## Installation

1. **Clone the repository and install dependencies:**
```bash
composer install
npm install
```

2. **Environment Setup:**
Copy the example environment file and generate the application key.
```bash
cp .env.example .env
php artisan key:generate
```
Ensure your `.env` is configured with your MySQL database credentials.

3. **Database Migration and Seeding:**
```bash
php artisan migrate --seed
```

4. **Storage Links:**
```bash
php artisan storage:link
```

5. **Start the Development Server:**
```bash
php artisan serve
```

## Testing

The project is configured to use an in-memory SQLite database for testing, ensuring fast test execution.
A custom command has been added to generate tests for all models and controllers:

1. **Generate all missing tests:**
```bash
php artisan make:comprehensive-tests
```

2. **Run the test suite:**
```bash
php artisan test
```

## Queue & Scheduler Setup

For background jobs (emails, certificates, large exports) and scheduled tasks (daily backups, data synchronization), configure the queue worker and scheduler.

**Start the queue worker:**
```bash
php artisan queue:work --tries=3 --timeout=90
```

**Run the scheduler:**
Add the following Cron entry to your server:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Overview

The backend uses RESTful conventions and Sanctum for token-based authentication.

- **Authentication:** `POST /api/login`, `POST /api/register`, `POST /api/verify-otp`
- **Public API:** `GET /api/public/courses`, `GET /api/public/jobs`
- **Student Portal:** `GET /api/student/dashboard`, `GET /api/student/courses`
- **Admin Portal:** `GET /api/admin/dashboard/summary` (Requires `super_admin` or `admin` role)

## Contribution Guidelines

1. Ensure all new controllers and models have accompanying Feature/Unit tests.
2. Avoid N+1 queries by using Eloquent's `with()` for eager loading.
3. Validate all incoming API requests using FormRequests.
4. Keep controllers thin; push complex business logic into Action classes or Services.
5. Use Laravel resources for JSON responses.
