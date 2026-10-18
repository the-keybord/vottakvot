# TikTak (`tik.tak.md`) — Universal Student Gateway

TikTak is a clean, lightweight, and ultra-fast web application tailored for teachers and students. It allows educators to publish links, notes, and file consoles with temporary or permanent 6-letter codes, accessible instantly via a search-engine-like gateway.

## Features

- **Search-Engine Homepage**: Fast, distraction-free search interface for students to enter their 6-letter class code.
- **Direct Link Support**: Access via `tik.tak.md/code=ABCDEF` or `tik.tak.md/ABCDEF` or `tik.tak.md/?code=ABCDEF`.
- **Resource Types**:
  1. **Redirect Link**: Instantly routes students to external materials (e.g. Google Drive, GitHub, Canvas, Zoom).
  2. **Shared Text / Notes**: Clean distraction-free note viewer (Dontpad-style) with 1-click copy, download as `.txt`, and visit counter.
  3. **Link List**: Clean distraction-free blank page with a title and a list of interactive destination buttons/redirects. Fully editable with in-place and dashboard button builders.
  4. **MinIO File Console**: Dedicated S3/MinIO bucket folder per code.
     - Anonymous uploads & downloads under 500 MB per file.
     - Files organized by upload date in a single-day full-width card view with day navigation arrows (skips empty days).
     - Heavy files (> 100 MB) highlighted with a glowing red warning alert for teachers.
     - Automatic duplicate filename suffixing (`_01`, `_02`, etc.).
     - 📌 **Pinned Materials**: Teachers can pin important files to a persistent top section.
     - Role-based security: only professors can delete or pin files.
  5. **Micro-hosting**: Dynamic web directory hosting index.php, HTML, CSS, JavaScript, and ZIP exports.
- **In-Page Teacher Login**:
  - Direct login modal on any code page to unlock teacher capabilities instantly.
- **Multi-Teacher Management**:
  - Logged-in teachers can discreetly create additional teacher accounts from the dashboard.
- **Expiration System**:
  - Expiration options: Never, 1 hour, 6 hours, 24 hours, 3 days, 7 days, 30 days, or Custom datetime.
  - Automatically denies access to expired codes with a clean notification.
  - 1-Click **+2 Hours** extension button for expired codes in the dashboard.
- **Professor Portal (`/prof`)**:
  - Secure login with session-based authorization.
  - Dashboard displaying active codes, total student visits, expired items, and file storage usage.
  - 6-Letter Random Code Generator with live validation.
  - Edit & Delete functionality (with automatic MinIO bucket folder cleanup).
  - Classroom Projector QR Code modal for easy in-class smartphone scanning.
- **Production Ready**: Native PHP with zero build steps or npm runtime dependencies. Works out of the box on Coolify, Docker, Nginx, Apache, or Caddy.

---

## Production Deployment & Configuration

Copy `.env.example` to `.env` or set environment variables in your deployment dashboard (e.g. Coolify, Docker, Kubernetes):

```env
# Database Settings
DB_HOST=your-db-host
DB_PORT=3306
DB_NAME=tiktak
DB_USER=tiktak_user
DB_PASS=your_strong_password

# MinIO / S3 Object Storage
MINIO_ENDPOINT=minio-f4qsjb22cxs0wdyypygkj480.85.120.144.58.sslip.io
MINIO_PORT=80
MINIO_USE_SSL=false
MINIO_BUCKET=tiktak
MINIO_ACCESS_KEY=admin
MINIO_SECRET_KEY=ChangeThisPassword123!

# Initial Administrator Seed (if database is empty)
ADMIN_USERNAME=prof
ADMIN_PASSWORD=your_initial_admin_password
ADMIN_NAME="Professor"
```

---

## Running with PHP Built-in Server

```bash
php -d upload_max_filesize=500M -d post_max_size=500M -d memory_limit=512M -S 0.0.0.0:8000 router.php
```

---

## Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name tik.tak.md;
    root /var/www/tiktak;
    index index.php;

    client_max_body_size 500M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "upload_max_filesize=500M \n post_max_size=500M \n memory_limit=512M";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
