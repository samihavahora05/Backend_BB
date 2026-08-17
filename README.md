<div align="left">
  <table>
    <tr>
      <td valign="center">
        <img src="https://raw.githubusercontent.com/samihavahora05/Frontend_BB/main/public/Boxxlogo.png" alt="Blueboxx DA Logo" width="140" />
      </td>
      <td valign="center">
        <h1 style="border-bottom: none; margin-bottom: 0; font-size: 2.2em; color: #1B2A6B;">Blueboxx DA Pvt. Ltd.</h1>
        <p style="font-size: 1.1em; color: #475569; margin-top: 4px;"><b>Enterprise RESTful API Engine — Laravel 11 Backend</b></p>
      </td>
    </tr>
  </table>

  <br />

  <p>
    <a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" /></a>
    <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" /></a>
    <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" /></a>
    <a href="https://razorpay.com/"><img src="https://img.shields.io/badge/Razorpay-Payment_Gateway-02042B?style=for-the-badge&logo=razorpay&logoColor=white" alt="Razorpay" /></a>
    <a href="https://laravel.com/docs/sanctum"><img src="https://img.shields.io/badge/Auth-Laravel_Sanctum-F43F5E?style=for-the-badge&logo=laravel&logoColor=white" alt="Sanctum Auth" /></a>
  </p>
</div>

---

> ⚙️ **Backend_BB** is the core RESTful API infrastructure for **Blueboxx DA**, delivering high-concurrency database queries, payment verification, real-time notification queueing, and role-based authorization.

---

## 🔥 Key Core Modules & Features

<table>
  <tr>
    <td width="50%" valign="top">
      <h3>🔒 Sanctum Auth & RBAC</h3>
      <ul>
        <li>Multi-guard Bearer Token authentication</li>
        <li>Role-based access: Student, Expert, Company, College, Admin</li>
        <li>Password history & security policies</li>
      </ul>
    </td>
    <td width="50%" valign="top">
      <h3>👨‍🏫 Expert Mentorship Engine</h3>
      <ul>
        <li>Dynamic expert profile & availability resolution</li>
        <li>Booking verification & schedule collision checks</li>
        <li>Automated notification dispatches</li>
      </ul>
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <h3>💳 Razorpay Payment API</h3>
      <ul>
        <li>Order creation & HMAC-SHA256 signature verification</li>
        <li>Order items mapping (Courses & Mentorship)</li>
        <li>Unified Payment History API (<code>/api/student/payments</code>)</li>
      </ul>
    </td>
    <td width="50%" valign="top">
      <h3>🔔 Notifications & Messaging</h3>
      <ul>
        <li>Real-time database notification queues</li>
        <li>Unread counters & mark-as-read handlers</li>
        <li>Formatted email notification dispatchers</li>
      </ul>
    </td>
  </tr>
</table>

---

## 🏛️ Database & Service Architecture

```text
               ┌─────────────────────────────────────┐
               │    Client App (Next.js / Axios)     │
               └──────────────────┬──────────────────┘
                                  │ (HTTP JSON API)
                                  ▼
 ┌──────────────────────────────────────────────────────────────────┐
 │                   Laravel RESTful API Engine                     │
 ├───────────────────┬──────────────────┬───────────────────────────┤
 │  Auth Middleware  │  Route Controllers│   Eloquent ORM Layer      │
 └─────────┬─────────┴────────┬─────────┴─────────────┬─────────────┘
           │                  │                       │
           ▼                  ▼                       ▼
 ┌──────────────────┐ ┌────────────────┐   ┌──────────────────────┐
 │ Sanctum Tokens   │ │ Razorpay SDK   │   │ MySQL Database       │
 └──────────────────┘ └────────────────┘   └──────────────────────┘
```

---

## 🛠️ Technology Stack

| Component | Technology | Description |
| :--- | :--- | :--- |
| **Framework** | Laravel 11.x | PHP Web & API Framework |
| **Language** | PHP 8.2+ | Modern Strong-Typed Scripting |
| **Database** | MySQL 8.0 | Relational Storage & Indexing |
| **Authentication** | Laravel Sanctum | API Token & Session Management |
| **Payment Gateway** | Razorpay PHP SDK | Signature Validation & Transactions |
| **Notification Engine** | Laravel Notifications | Database & Queue Notifications |

---

## 📁 Key Controllers & Routes Structure

```text
app/
├── Http/
│   └── Controllers/
│       ├── Api/
│       │   ├── CheckoutController.php           # Razorpay Order Creation & Verification
│       │   ├── NotificationController.php         # Real-time Notifications Manager
│       │   ├── Public/
│       │   │   ├── PublicExpertController.php   # Expert Search & Session Booking API
│       │   │   └── PublicCourseController.php   # Public Course Catalog API
│       │   └── Student/
│       │       ├── StudentOrderController.php   # Payment History & Total Spent API
│       │       └── StudentProfileController.php # Student Profile & Resume Manager
├── Models/                                     # Eloquent Models (Order, MentorBooking, Payment, User)
├── Notifications/                              # Notification Classes (BookingConfirmedNotification)
└── Services/
    └── Payments/                               # Payment Gateway Interfaces & Gateways
routes/
└── api.php                                     # RESTful API Endpoints & Middleware Groups
```

---

## ⚡ Quick Start Guide

### 1. Prerequisites
- PHP >= 8.2 (with OpenSSL, PDO, Mbstring)
- Composer
- MySQL Database

### 2. Installation & Setup
```bash
git clone https://github.com/samihavahora05/Backend_BB.git
cd Backend_BB
composer install
```

### 3. Environment Configuration (`.env`)
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

RAZORPAY_KEY_ID=rzp_test_your_key_id
RAZORPAY_KEY_SECRET=your_razorpay_secret
```

### 4. Run Migrations & Start Server
```bash
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
API endpoints will be live at **`http://localhost:8000/api`**.

---

<div align="center">
  <p>Privately Developed for <b>Blueboxx DA Pvt. Ltd.</b> • All Rights Reserved</p>
</div>
