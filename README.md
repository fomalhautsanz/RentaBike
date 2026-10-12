# RentaBike 🚲

A dual-portal bike rental management system built for **USeP Energy Park**, developed by **CyberCrows** at the University of Southeastern Philippines, Tagum-Mabini Campus. Built with Laravel 13, Blade templating, Tailwind CSS, and MySQL.

---

## Overview

RentaBike is a role-based bike rental management platform with two separate portals:

- **Admin Portal** — A full-featured web dashboard for administrators to manage staff, bikes, rentals, reports, and system activity.
- **Staff Portal** — A mobile-first interface for on-ground staff to process rentals via QR code scanning, manage inventory (with permission-gated access), and file issue reports.

---

## Portals & Access

| Portal | URL | Description |
|---|---|---|
| Admin | `/admin/login` | Full management dashboard |
| Staff | `/staff/login` | Mobile-first rental operations |

---

## Features

### Admin Portal
- **Dashboard Overview** — Live stats (total bikes, active rentals, available bikes, today's revenue) with charts: weekly rental trends, bike type distribution, revenue vs. rentals, and peak rental hours
- **Staff Management** — Add, edit, and deactivate staff accounts with dynamic, per-staff privilege assignment via checkboxes
- **Bike Inventory** — Full CRUD on the bike fleet with QR code assignment, type, model, make, and condition tracking
- **Maintenance & Reports** — View and resolve issue reports filed by staff (damage, missing bikes, other)
- **Reports & Analytics** — Generate period-based reports; export rental data as CSV
- **Activity Logs** — Full audit trail of all admin and staff actions across the system

### Staff Portal (Mobile-First)
- **Home Dashboard** — Live bike status counters (available, rented, under repair, total) with a quick bike list
- **QR Code Scanner** — Scan a bike's QR code to open the rental form
- **Rental Management** — Process rentals with borrower name, contact, borrow/return time, and valid ID upload; countdown timer on the success screen
- **Bike Inventory** — Browse inventory by category with search and filter by type and status; edit/delete gated by admin-assigned permissions
- **Issue Reporting** — File damage, missing bike, or other issue reports with optional photo evidence
- **Confirmation Dialogs** — Every destructive or submitting action requires a confirmation step before proceeding

### Security
- **Separate authentication** for admin and staff — no shared login
- **Rate limiting** — accounts lock for 3 minutes after 3 consecutive failed login attempts
- **Email hashing** — stored as HMAC-SHA-256 (keyed hash), never in plaintext
- **Password hashing** — bcrypt via Laravel's `Hash` facade
- **Dynamic permissions** — admin can grant or revoke specific privileges per staff member (view/add/edit/delete inventory, handle maintenance, manage staff)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.5.2 |
| Templating | Blade |
| Styling | Tailwind CSS |
| Database | MySQL (managed via MySQL Workbench) |
| Charts | Chart.js |
| Version Control | Git / GitHub |

---

## Database Architecture

The database uses a custom ERD-based schema — **not** Laravel's default `users` table. It includes:

**Tables:** `admin`, `staff`, `bicycle`, `borrower`, `rental`, `overtime`, `payment`, `reports`, `issue_report`, `feedback`, `activity_log`

**Views:** `vw_active_rentals`, `vw_rental_history`, `vw_bicycle_inventory`, `vw_revenue_summary`, `vw_staff_activity_log`

**Stored Procedures:** `sp_open_rental`, `sp_close_rental`, `sp_generate_report`, `sp_update_bike_status`, `sp_manage_staff`

**Functions:** `fn_compute_rental_fee`, `fn_compute_overtime_penalty`, `fn_is_bike_available`

**Triggers:** 7 triggers covering bike availability validation, automatic status updates, overtime fee sync, payment settlement logging, and staff deletion protection

---

## Requirements

- PHP 8.5+
- Composer
- Node.js & npm
- MySQL 8.0+
- Laravel 13

---

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/fomalhautsanz/RentaBike.git
   cd RentaBike/my-project
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure environment**

   Edit `.env` with your credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rentabike
   DB_USERNAME=root
   DB_PASSWORD=your_password

   EMAIL_HASH_KEY=your_long_random_secret_key
   ```

7. **Create the database** in MySQL Workbench
   ```sql
   CREATE DATABASE rentabike;
   ```

8. **Run migrations** (creates all tables, views, stored procedures, functions, and triggers)
   ```bash
   php artisan migrate
   ```

9. **Create sessions and cache tables**
   ```bash
   php artisan session:table
   php artisan cache:table
   php artisan migrate
   ```

10. **Seed an admin account**
    ```bash
    php artisan tinker
    ```
    ```php
    \App\Models\Admin::create([
        'username'      => 'admin',
        'full_name'     => 'Admin User',
        'email'         => 'admin@rentabike.com',
        'email_hash'    => hash_hmac('sha256', 'admin@rentabike.com', env('EMAIL_HASH_KEY')),
        'password_hash' => bcrypt('your_password'),
    ]);
    ```

11. **Build assets and start the server**
    ```bash
    npm run dev
    php artisan serve
    ```

    Admin portal → `http://localhost:8000/admin/login`
    Staff portal → `http://localhost:8000/staff/login`

---

## Project Structure

```
my-project/
├── app/
│   ├── Helpers/
│   │   └── HashHelper.php          # HMAC email hashing utility
│   ├── Http/Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php       # Admin auth with rate limiting
│   │   │   └── StaffLoginController.php  # Staff auth with rate limiting
│   │   └── Admin/
│   │       └── DashboardController.php
│   └── Models/
│       ├── Admin.php
│       ├── Staff.php
│       └── Bicycle.php
├── database/
│   └── migrations/                  # 14+ migration files (ERD-based schema)
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── admin.blade.php      # Admin portal layout
│       │   └── staff.blade.php      # Staff portal layout (mobile-first)
│       ├── admin/
│       │   ├── dashboard.blade.php
│       │   ├── pages/               # _dashboard, _staff, _bikes, _maintenance, _rentals, _reports
│       │   ├── modals/              # _staff_modals, _bike_modals, _report_modal
│       │   └── scripts/             # _dashboard_charts, _staff_scripts, _bike_scripts
│       ├── staff/
│       │   ├── home.blade.php
│       │   ├── pages/               # _login, _home, _scanner, _inventory, _report, _rental_form, _success
│       │   ├── modals/              # _bike_modals
│       │   └── scripts/             # _staff_scripts
│       └── auth/
│           └── login.blade.php      # Admin login view
└── routes/
    └── web.php
```

---

## Branching Strategy

```
main            ← stable, production-ready code only
└── develop     ← integration branch; merge all features here first
    ├── feature/admin-dashboard
    ├── feature/admin-user-accounts
    ├── feature/admin-inventory-management
    ├── feature/admin-rental-oversight
    ├── feature/admin-reports-analytics
    ├── feature/admin-feedback-management
    ├── feature/admin-activity-logs
    ├── feature/staff-dashboard
    ├── feature/staff-qr-scanning
    ├── feature/staff-bike-monitoring
    ├── feature/staff-rental-management
    ├── feature/staff-issue-handling
    └── feature/staff-maintenance-handling
```

**Workflow:** `feature/xxx` → Pull Request → `develop` → Pull Request → `main`

---

## Roadmap

- [x] Admin and staff authentication with rate limiting
- [x] Email hashing (HMAC-SHA-256) and password hashing (bcrypt)
- [x] ERD-based database with stored procedures, views, functions, and triggers
- [x] Admin dashboard with live charts
- [x] Staff management with dynamic per-staff permissions
- [x] Bike inventory management
- [x] QR-based rental processing (staff portal)
- [x] Issue reporting with photo evidence
- [x] Activity logging
- [ ] Full rental processing backend (open/close via stored procedures)
- [ ] Reports & Analytics page backend
- [ ] Email notifications on rental events
- [ ] Payment gateway integration
- [ ] Real-time bike availability updates

---

## Team

**CyberCrows** — University of Southeastern Philippines, Tagum-Mabini Campus
College of Teacher Education and Technology

---

**Happy renting! 🚴**
