# DS Class — Digital Smart Class

Self-contained PHP/MySQL web app (students, teachers, admin, accountant).

## Folder layout

```
dsclass/
├── index.php               Home page
├── login.php               Login
├── register.php            Student registration
├── logout.php              End session
├── courses.php             Public course catalog
├── course-details.php      Public course page
├── about.php               About page
├── contact.php             Contact page
├── notifications.php       User notifications
├── health.php              Health/status check
├── setup-admin.php         One-time first-admin setup
├── .htaccess               Blocks config/, includes/, database/, docs and source files
├── admin/                  Admin dashboard (users, courses, payments, reports, settings)
├── accountant/             Finance: payment review, revenue, reports
├── student/                Learning area (my courses, payments, progress, file streaming)
├── teacher/                Course building (modules, lessons, materials, students)
├── assets/                 css/, js/, images/
├── config/
│   └── database.php        DB connection + site URL helper
├── database/
│   └── digital_smart_class.sql   Schema (structure only, no data)
├── includes/               Shared auth guards, header/footer/navbar, helpers
└── uploads/                User uploads (web access denied; served via student/file.php)
```

## Setup on XAMPP / shared hosting

1. Copy this `dsclass` folder into your web root (`htdocs` on XAMPP, `public_html` on cPanel).
2. Create a MySQL database (e.g. `digital_smart_class`).
3. Import `database/digital_smart_class.sql` (phpMyAdmin → Import).
4. Edit `config/database.php` defaults — host, database name, user, password — or
   provide `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT` environment variables.
5. Make sure `uploads/` and its subfolders are writable by the web server
   (on Linux: `chmod -R 775 uploads`).
6. Open `setup-admin.php` once in the browser to create the first administrator,
   then log in at `login.php`.

## Notes

- Requires PHP 8.0+ with `pdo_mysql` (and `mysqli`), MySQL 5.7+/8.0.
- All SQL uses PDO prepared statements; passwords use `password_hash()`.
- Role guards live in `includes/*-auth.php`; every dashboard page requires login.
- `uploads/` must never be exposed directly — course media is streamed by
  `student/file.php` after an enrollment check.
