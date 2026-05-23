# Zy4Store + Zy4Panel

Zy4Store + Zy4Panel adalah project **web store hosting + game server panel custom** yang dibuat dari nol. Project ini tidak memakai Pterodactyl, Wings, WHMCS, CyberPanel, atau panel hosting lain.

- **Zy4Store**: website store untuk jual paket hosting.
- **Zy4Panel**: panel client/admin untuk kelola server game.
- **Zy4Daemon**: agent node berbasis Go untuk menjalankan Docker container.

> Target production: Ubuntu 22.04, Nginx, PHP 8.3+, Laravel 13, MySQL/MariaDB, Redis, Docker, Go.

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Struktur Project](#struktur-project)
- [Requirement Server](#requirement-server)
- [Quick Install](#quick-install)
- [Setup Database](#setup-database)
- [Konfigurasi Panel](#konfigurasi-panel)
- [Install Panel](#install-panel)
- [Install Zy4Daemon](#install-zy4daemon)
- [Setup Docker Images](#setup-docker-images)
- [Setup Nginx](#setup-nginx)
- [Setup SSL](#setup-ssl)
- [Login Admin](#login-admin)
- [Tutorial Pemakaian](#tutorial-pemakaian)
- [API Endpoint](#api-endpoint)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)

## Fitur

### Store

- Landing page dark theme.
- Produk hosting: Minecraft, SAMP, FiveM, Discord Bot, VPS.
- Detail produk dan paket.
- Register dan login user.
- Cart sederhana.
- Checkout manual payment.
- Invoice.
- Upload bukti pembayaran.
- Admin approve/reject payment.
- Auto create server setelah invoice/payment approved.
- Client area untuk order, invoice, dan server.

### Client Panel

- Dashboard server dengan status, CPU, RAM, disk, uptime, IP, dan port.
- Power action: start, stop, restart, kill.
- Console realtime WebSocket dari Docker logs.
- Input command ke server.
- File manager basic: list, upload, create, rename, delete.
- Database basic: create, delete, lihat host/port/user/password.
- Backup basic: create, download, restore, delete.
- Network allocation list.
- Startup config: Docker image, startup command, environment variables.
- Settings server dan SFTP info placeholder.
- Activity logs.

### Admin Panel

- Dashboard admin.
- Manage users.
- Manage products.
- Manage plans/packages.
- Manage orders.
- Manage invoices.
- Manage payments.
- Manage servers.
- Manage nodes.
- Manage allocations/ports.
- Manage Docker templates.
- Suspend/unsuspend server.
- View activity logs.
- Global settings.

### Zy4Daemon

- HTTP API dengan bearer token.
- Docker container per server.
- Create/start/stop/restart/kill container.
- Stats container.
- File manager dengan path guard.
- Backup dan restore `tar.gz`.
- WebSocket console.
- Folder server: `/var/lib/zy4daemon/servers/{server_uuid}/`.
- Folder backup: `/var/lib/zy4daemon/backups/{server_uuid}/`.

## Tech Stack

- Backend: Laravel 13
- Frontend: Blade, Tailwind CSS, Alpine.js
- Database: MySQL/MariaDB
- Queue/cache: Redis
- Daemon node: Go
- Container runtime: Docker
- Web server: Nginx
- OS target: Ubuntu 22.04

## Struktur Project

```text
zy4hosting/
├── panel/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── .env.example
│   ├── composer.json
│   └── package.json
├── daemon/
│   ├── main.go
│   ├── docker.go
│   ├── file_handlers.go
│   ├── backup_handlers.go
│   ├── console_handlers.go
│   ├── .env.example
│   └── zy4daemon.service
├── installer/
│   ├── install-panel.sh
│   ├── install-daemon.sh
│   ├── docker-setup.sh
│   ├── nginx-panel.conf
│   └── supervisor.conf
├── docker-images/
│   ├── minecraft/
│   ├── samp/
│   ├── nodejs/
│   └── generic/
└── README.md
```

## Requirement Server

Minimal:

- Ubuntu 22.04 LTS.
- CPU 2 core.
- RAM 4 GB.
- Disk 30 GB.
- Domain/subdomain, contoh `panel.example.com`.
- Root/sudo access.
- Port `80` dan `443` untuk web.
- Port `7443` untuk daemon jika tidak diproxy lewat Nginx.

Recommended:

- CPU 4 core.
- RAM 8 GB+.
- Disk SSD/NVMe.
- Panel dan daemon bisa satu server untuk testing, tapi production lebih bagus dipisah.

## Quick Install

Clone atau upload project ke server:

```bash
cd /var/www
sudo git clone https://github.com/veruy2811/zy4hosting.git zy4hosting
cd /var/www/zy4hosting
```

Jika upload manual tanpa Git:

```bash
sudo mkdir -p /var/www/zy4hosting
sudo cp -R ./zy4hosting/* /var/www/zy4hosting/
cd /var/www/zy4hosting
```

Install panel:

```bash
sudo DOMAIN=panel.example.com bash installer/install-panel.sh
```

Install daemon:

```bash
sudo bash installer/install-daemon.sh
```

Build Docker images:

```bash
sudo bash installer/docker-setup.sh
```

## Setup Database

Masuk ke MySQL/MariaDB:

```bash
sudo mysql
```

Buat database dan user:

```sql
CREATE DATABASE zy4hosting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zy4hosting'@'127.0.0.1' IDENTIFIED BY 'password-kuat';
GRANT ALL PRIVILEGES ON zy4hosting.* TO 'zy4hosting'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Sesuaikan kredensial ini di file `.env` panel.

## Konfigurasi Panel

Masuk ke folder panel:

```bash
cd /var/www/zy4hosting/panel
cp .env.example .env
```

Edit `.env`:

```env
APP_NAME=Zy4Store
APP_ENV=production
APP_DEBUG=false
APP_URL=https://panel.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zy4hosting
DB_USERNAME=zy4hosting
DB_PASSWORD=password-kuat

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database

ADMIN_NAME=Zy4 Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=ChangeMe123!

DAEMON_SECRET=isi-token-yang-sama-dengan-node
DAEMON_BASE_PATH=/var/lib/zy4daemon
DAEMON_DEFAULT_URL=http://127.0.0.1:7443

PAYMENT_MODE=manual
UPLOAD_MAX_SIZE=100M
```

Generate app key dan migrate:

```bash
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
```

## Install Panel

Installer panel akan memasang:

- PHP 8.3 dan extension Laravel.
- Composer.
- Nginx.
- MariaDB.
- Redis.
- Node.js.
- Supervisor worker.
- Laravel dependencies.
- Vite build.
- Migration dan seeder.
- Permission folder storage/cache.
- Scheduler cron.

Jalankan:

```bash
cd /var/www/zy4hosting
sudo DOMAIN=panel.example.com bash installer/install-panel.sh
```

Setelah selesai, cek:

```bash
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status redis-server
sudo supervisorctl status
```

## Install Zy4Daemon

Zy4Daemon bisa dipasang di server yang sama atau node terpisah.

```bash
cd /var/www/zy4hosting
sudo bash installer/install-daemon.sh
```

Installer daemon akan:

- Install Docker.
- Install Go build tools.
- Build binary `zy4daemon`.
- Membuat folder `/var/lib/zy4daemon`.
- Membuat service systemd.
- Start daemon.

Cek token daemon:

```bash
sudo cat /opt/zy4daemon/.env
```

Contoh `.env` daemon:

```env
ZY4DAEMON_LISTEN=:7443
DAEMON_SECRET=token-random-yang-panjang
DAEMON_BASE_PATH=/var/lib/zy4daemon
DOCKER_HOST=unix:///var/run/docker.sock
PANEL_URL=https://panel.example.com
```

Cek service:

```bash
sudo systemctl status zy4daemon
sudo journalctl -u zy4daemon -f
```

## Setup Docker Images

Build semua image bawaan:

```bash
cd /var/www/zy4hosting
sudo bash installer/docker-setup.sh
```

Image yang dibuat:

- `zy4/minecraft:latest`
- `zy4/samp:latest`
- `zy4/nodejs:latest`
- `zy4/generic:latest`

Cek:

```bash
sudo docker images | grep zy4
```

## Setup Nginx

Contoh config ada di:

```text
installer/nginx-panel.conf
```

Install manual:

```bash
sudo cp installer/nginx-panel.conf /etc/nginx/sites-available/zy4panel.conf
sudo sed -i 's/panel.example.com/domain-kamu.com/g' /etc/nginx/sites-available/zy4panel.conf
sudo ln -sfn /etc/nginx/sites-available/zy4panel.conf /etc/nginx/sites-enabled/zy4panel.conf
sudo nginx -t
sudo systemctl reload nginx
```

Config ini sudah support:

- PHP-FPM.
- Upload besar `100m`.
- Laravel public root.
- WebSocket reverse proxy untuk daemon di `/daemon/`.

Jika daemon mau diproxy lewat domain panel, set node URL di admin:

```text
https://panel.example.com/daemon
```

Jika daemon langsung ke IP node:

```text
http://IP_NODE:7443
```

## Setup SSL

Install Certbot:

```bash
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-nginx
```

Generate SSL:

```bash
sudo certbot --nginx -d panel.example.com
sudo systemctl reload nginx
```

Auto renew biasanya aktif otomatis. Cek:

```bash
sudo certbot renew --dry-run
```

## Login Admin

Admin default diambil dari `.env`:

```env
ADMIN_NAME=Zy4 Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=ChangeMe123!
```

Login:

```text
https://panel.example.com/login
```

Seeder juga membuat demo client:

```text
Email: client@example.com
Password: client12345
```

## Tutorial Pemakaian

### 1. Membuat Node

1. Install Zy4Daemon di node server.
2. Ambil token dari `/opt/zy4daemon/.env`.
3. Login admin ke Zy4Panel.
4. Masuk ke **Admin -> Nodes**.
5. Isi data node:
   - Name: `Local Node 01`
   - Daemon URL: `http://IP_NODE:7443`
   - Token: isi dari `DAEMON_SECRET`
   - Public IP: IP publik node
6. Simpan node.

### 2. Membuat Allocation Port

1. Masuk ke **Admin -> Allocations**.
2. Pilih node.
3. Isi IP bind:
   - `0.0.0.0` untuk bind semua interface.
   - IP publik/private tertentu jika ingin lebih spesifik.
4. Isi port, contoh `25565`.
5. Isi alias dengan IP publik jika perlu.
6. Simpan allocation.

### 3. Membuat Produk

1. Masuk ke **Admin -> Products**.
2. Buat produk, contoh:
   - Name: `Minecraft Hosting`
   - Slug: `minecraft-hosting`
   - Category: `game`
   - Active: yes
3. Simpan produk.

### 4. Membuat Plan

1. Masuk ke **Admin -> Plans**.
2. Pilih produk.
3. Isi resource:
   - RAM: `1024`
   - CPU: `1`
   - Disk: `5120`
   - Database limit: `1`
   - Backup limit: `1`
   - Allocation limit: `1`
4. Isi Docker image:

```text
zy4/minecraft:latest
```

5. Isi startup command:

```text
java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar nogui
```

6. Simpan plan.

### 5. Client Order Server

1. Client buka halaman produk.
2. Pilih plan.
3. Checkout.
4. Upload bukti pembayaran di invoice.
5. Admin approve payment.
6. Panel otomatis membuat server di node.
7. Client buka **Client Area -> Servers**.

### 6. Mengelola Server

Client bisa membuka server lalu memakai menu:

- **Dashboard**: lihat status dan resource.
- **Console**: realtime log dan kirim command.
- **Files**: upload, create, rename, delete file.
- **Databases**: create/delete database record.
- **Backups**: create, download, restore, delete backup.
- **Network**: lihat IP/port allocation.
- **Startup**: update image, command, environment.
- **Settings**: rename, reinstall, delete server.
- **Activity**: audit log aktivitas.

## API Endpoint

### Panel API

```text
POST   /api/daemon/auth
POST   /api/daemon/heartbeat
GET    /api/servers
GET    /api/servers/{id}
POST   /api/servers/{id}/start
POST   /api/servers/{id}/stop
POST   /api/servers/{id}/restart
POST   /api/servers/{id}/kill
GET    /api/servers/{id}/stats
GET    /api/servers/{id}/files
POST   /api/servers/{id}/files/upload
POST   /api/servers/{id}/files/create
PATCH  /api/servers/{id}/files/rename
DELETE /api/servers/{id}/files/delete
GET    /api/servers/{id}/databases
POST   /api/servers/{id}/databases
DELETE /api/servers/{id}/databases/{database}
GET    /api/servers/{id}/backups
POST   /api/servers/{id}/backups
POST   /api/servers/{id}/backups/{backup}/restore
DELETE /api/servers/{id}/backups/{backup}
```

### Daemon API

```text
POST      /servers/create
POST      /servers/{uuid}/start
POST      /servers/{uuid}/stop
POST      /servers/{uuid}/restart
POST      /servers/{uuid}/kill
GET       /servers/{uuid}/stats
GET       /servers/{uuid}/files
POST      /servers/{uuid}/files/upload
POST      /servers/{uuid}/files/write
POST      /servers/{uuid}/files/mkdir
PATCH     /servers/{uuid}/files/rename
DELETE    /servers/{uuid}/files/delete
POST      /servers/{uuid}/backup
GET       /servers/{uuid}/backup
DELETE    /servers/{uuid}/backup
POST      /servers/{uuid}/restore
WebSocket /servers/{uuid}/console
```

Semua request panel ke daemon memakai bearer token:

```http
Authorization: Bearer DAEMON_SECRET
```

## Security

- Password user di-hash oleh Laravel.
- Form web memakai CSRF.
- Route client/admin memakai auth middleware.
- User hanya bisa akses server miliknya.
- Admin dan staff bisa akses semua server.
- Secret daemon tidak di-hardcode.
- Token node disimpan terenkripsi di database.
- File manager dicegah dari path traversal.
- Daemon juga memvalidasi path agar tidak keluar dari folder server.
- Upload bukti pembayaran dibatasi mime `jpg`, `jpeg`, `png`, `webp`, `pdf`.
- Login dan API diberi rate limit.
- Activity log mencatat aksi penting.

## Troubleshooting

### Panel error 500

```bash
cd /var/www/zy4hosting/panel
php artisan optimize:clear
php artisan config:clear
tail -f storage/logs/laravel.log
```

### Permission error storage/cache

```bash
cd /var/www/zy4hosting/panel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue tidak jalan

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart zy4panel-worker:*
sudo supervisorctl status
```

### Scheduler tidak jalan

Cek cron user `www-data`:

```bash
sudo crontab -u www-data -l
```

Isi yang dibutuhkan:

```cron
* * * * * cd /var/www/zy4hosting/panel && php artisan schedule:run >> /dev/null 2>&1
```

### Daemon tidak jalan

```bash
sudo systemctl status zy4daemon
sudo journalctl -u zy4daemon -f
```

### Docker tidak jalan

```bash
sudo systemctl status docker
sudo docker ps
```

### Server gagal dibuat

Cek ini:

- Node aktif di admin.
- Token node sama dengan `DAEMON_SECRET`.
- `daemon_url` benar.
- Ada allocation port kosong.
- Docker image sudah dibuild atau bisa dipull.
- Docker service running.

### Console WebSocket gagal

Cek ini:

- `daemon_url` bisa diakses panel.
- Nginx reverse proxy memiliki header `Upgrade`.
- Port `7443` terbuka jika tidak memakai proxy.
- Jam panel dan node sinkron dengan NTP.

Install NTP:

```bash
sudo apt-get install -y chrony
sudo systemctl enable --now chrony
```

### Upload file besar gagal

Cek Nginx:

```nginx
client_max_body_size 100m;
```

Cek `.env`:

```env
UPLOAD_MAX_SIZE=100M
```

Cek PHP:

```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

## Command Berguna

```bash
# Panel
cd /var/www/zy4hosting/panel
php artisan migrate --seed --force
php artisan optimize:clear
php artisan config:cache
npm run build

# Queue
sudo supervisorctl restart zy4panel-worker:*

# Daemon
sudo systemctl restart zy4daemon
sudo journalctl -u zy4daemon -f

# Docker
sudo docker ps
sudo docker logs CONTAINER_ID
```

## Upload ke GitHub

Dari folder project:

```bash
cd /var/www/zy4hosting
git init
git add .
git commit -m "Initial Zy4Store Zy4Panel source"
git branch -M main
git remote add origin https://github.com/USERNAME/zy4hosting.git
git push -u origin main
```

Jangan commit file rahasia:

- `panel/.env`
- `daemon/.env`
- file backup
- file upload payment
- folder `vendor/`
- folder `node_modules/`

File `.gitignore` sudah disiapkan untuk kebutuhan dasar.

## Roadmap

- Subuser permission granular per server.
- Schedule task runner.
- SFTP native.
- Provisioning database fisik per node.
- Recurring invoice dan billing automation.
- Notifikasi email/WhatsApp.
- Admin audit log global lebih detail.
- Template installer game yang lebih lengkap.

## License

MIT. Silakan gunakan, modifikasi, dan kembangkan sesuai kebutuhan.
