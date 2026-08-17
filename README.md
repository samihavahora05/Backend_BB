<div align="center">
  <img src="https://raw.githubusercontent.com/samihavahora05/Frontend_BB/main/public/Boxxlogo.png" alt="Blueboxx DA Logo" width="160" />
  <h2>⚙️ Blueboxx DA — Backend RESTful API Engine</h2>
  <p><b>Powering Authentication, Mentorship Bookings, and Razorpay Payments</b></p>

  <p>
    <a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel" /></a>
    <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php" alt="PHP" /></a>
    <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql" alt="MySQL" /></a>
    <a href="https://razorpay.com/"><img src="https://img.shields.io/badge/Razorpay-Gateway-02042B?style=for-the-badge&logo=razorpay" alt="Razorpay" /></a>
  </p>
</div>

---

## 📖 Overview

A robust, enterprise-grade RESTful API & Management Engine built with **Laravel 11** and **MySQL**. **Backend_BB** powers the core infrastructure for **Blueboxx DA**, managing authentication, expert session bookings, Razorpay payment verification, student learning analytics, company hiring portals, and database notifications.

---

## ✨ Key Features & Modules

- 🔒 **Sanctum Authentication & RBAC**: Secure API token authentication supporting multi-role access control (`student`, `expert`, `company`, `college`, `admin`).
- 👨‍🏫 **Expert Mentorship System**: Dynamic expert profile resolution, availability slot scheduling, booking verification, and notification dispatching.
- 💳 **Razorpay Payment Gateway**: Order generation, HMAC-SHA256 signature verification, transaction logging, order items mapping, and payment history APIs (`/api/student/payments`).
- 📚 **LMS & Course Management**: Course categories, video lesson progress, virtual live classes, quiz submissions, and certificate issuance records.
- 💼 **Job & Internship Applications**: Comprehensive application workflows, status tracking (`Applied`, `Shortlisted`, `Selected`, `Rejected`), and hiring company seeders.
- 🔔 **Notification Infrastructure**: Real-time database notification queueing (`BookingConfirmedNotification`, `PlatformNotification`), mark-as-read triggers, and email notifications.

---

## 🛠️ Technology Stack

- **Framework**: [Laravel 11.x](https://laravel.com/)
- **Language**: PHP 8.2+
- **Database**: [MySQL](https://www.mysql.com/) / MariaDB
- **Authentication**: Laravel Sanctum (Bearer Token)
- **Payment Gateway**: [Razorpay API SDK Integration](https://razorpay.com/docs/)
- **Queue & Mailer**: Database Queue Driver & Blade Mail Templates

---

## 📁 Key Architecture & Controllers

```text
app/
├── Http/
│   └── Controllers/
│       ├── Api/
│       │   ├── CheckoutController.php           # Razorpay Order Creation & Verification
│       │   ├── NotificationController.php         # Database Notifications Manager
│       │   ├── Public/
│       │   │   ├── PublicExpertController.php   # Expert Profile & Session Booking API
│       │   │   └── PublicCourseController.php   # Course Catalog API
│       │   └── Student/
│       │       ├── StudentOrderController.php   # Payment History & Total Spent API
│       │       └── StudentProfileController.php # Student Profile & Resume Manager
├── Models/                                     # Eloquent Models (Order, MentorBooking, Payment, User)
├── Notifications/                              # Notification Classes (BookingConfirmedNotification)
└── Services/
    └── Payments/                               # Payment Gateway Interfaces & Implementations
routes/
└── api.php                                     # RESTful API Endpoints & Middleware Groups
```

---

## ⚡ Getting Started

### 1. Prerequisites
- PHP >= 8.2 with OpenSSL, PDO, Mbstring, and Tokenizer extensions
- Composer
- MySQL Database

### 2. Installation
```bash
git clone https://github.com/samihavahora05/Backend_BB.git
cd Backend_BB
composer install
```

### 3. Environment Setup
Create a `.env` file from `.env.example`:
```env
APP_NAME="Blueboxx DA API"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backend_bb
DB_USERNAME=root
DB_PASSWORD=

RAZORPAY_KEY_ID=your_razorpay_key_id
RAZORPAY_KEY_SECRET=your_razorpay_key_secret
```

### 4. Run Database Migrations & Seeders
```bash
php artisan key:generate
php artisan migrate --seed
```

### 5. Start Local API Server
```bash
php artisan serve
```
API endpoints will be available at [http://localhost:8000/api](http://localhost:8000/api).

---

## 📜 License
Privately developed for **Blueboxx DA Pvt. Ltd.** All rights reserved.
