# ✅ Server Environment Checklist (Laravel 12)

## 1. Tujuan
Dokumen ini menerangkan prosedur standard bagi proses deployment sistem Helpdesk
bagi memastikan kestabilan, keselamatan dan konsistensi antara environment.

## 2. Struktur Environment & Branch

| Environment  | Branch Git        | Tujuan |
|-------------|------------------|--------|
| Development | server-dev       | Pembangunan & ujian awal |
| Staging     | server-staging   | UAT & semakan |
| Production  | main             | Sistem live |

## Aliran Kod (Git → Server)

[ Programmer Local ]
        |
        |  git push
        v
[ GitHub Repository ]
        |
        |-------------------------------|
        |                               |
  branch: server-dev    branch: server-staging 
        |                               |
        v                               v
[ DEV Server ] (*.*.*.181)       [ STAGING Server ] (*.*.*.182)
 (git pull)                      (git pull)
        |
        |  merge approved
        v
  branch: main (production)
        |
        v
[ PRODUCTION Server ] (*.*.*.180)
 (git pull + deploy)

## 3. SOP Deployment – DEV Server

## Langkah:
bash
cd /var/www/html/ehelp
git pull origin server-dev
composer install
php artisan migrate if berkenaan
php artisan optimize:clear
php artisan optimize
npm run build - if ada perubahan fronted .js dan .css
 
## .env dev
APP_ENV=local
APP_DEBUG=true

## 4. SOP Deployment – Staging Server
cd /var/www/html/ehelp
git pull origin server-staging
composer install --no-dev
php artisan migrate
npm install
npm run build
php artisan optimize

## .env staging
APP_ENV=staging
APP_DEBUG=false

## 5. SOP Deployment – Production Server ✅
cd /var/www/html/ehelp

git pull origin main

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan optimize:clear
php artisan optimize
php artisan storage:link

## .env production

APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
