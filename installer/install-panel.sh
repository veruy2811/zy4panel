#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-panel.example.com}"
APP_DIR="${APP_DIR:-/var/www/zy4hosting}"
PANEL_DIR="${PANEL_DIR:-$APP_DIR/panel}"
PHP_VERSION="${PHP_VERSION:-8.3}"

if [[ $EUID -ne 0 ]]; then
  echo "Run as root: sudo bash installer/install-panel.sh"
  exit 1
fi

apt-get update
apt-get install -y software-properties-common ca-certificates curl gnupg unzip git lsb-release
add-apt-repository -y ppa:ondrej/php
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get update
apt-get install -y nginx mariadb-server redis-server supervisor nodejs \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-mysql php${PHP_VERSION}-redis \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-zip \
  php${PHP_VERSION}-bcmath php${PHP_VERSION}-gd php${PHP_VERSION}-intl

if ! command -v composer >/dev/null 2>&1; then
  EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
  if [[ "$EXPECTED" != "$ACTUAL" ]]; then
    echo "Composer installer checksum mismatch"
    exit 1
  fi
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

mkdir -p "$APP_DIR"
if [[ ! -d "$PANEL_DIR" ]]; then
  echo "Copy project to $APP_DIR first, then rerun this installer."
  exit 1
fi

cd "$PANEL_DIR"
if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i "s#APP_URL=.*#APP_URL=https://${DOMAIN}#g" .env
fi

composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate --force
php artisan storage:link || true
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache"
find "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
find "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;

cp "$APP_DIR/installer/nginx-panel.conf" /etc/nginx/sites-available/zy4panel.conf
sed -i "s#panel.example.com#${DOMAIN}#g" /etc/nginx/sites-available/zy4panel.conf
sed -i "s#/var/www/zy4hosting/panel/public#${PANEL_DIR}/public#g" /etc/nginx/sites-available/zy4panel.conf
ln -sfn /etc/nginx/sites-available/zy4panel.conf /etc/nginx/sites-enabled/zy4panel.conf
nginx -t
systemctl reload nginx

cp "$APP_DIR/installer/supervisor.conf" /etc/supervisor/conf.d/zy4panel-worker.conf
sed -i "s#/var/www/zy4hosting/panel#${PANEL_DIR}#g" /etc/supervisor/conf.d/zy4panel-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl restart zy4panel-worker:* || true

(crontab -u www-data -l 2>/dev/null | grep -v "schedule:run"; echo "* * * * * cd ${PANEL_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -

systemctl enable --now redis-server mariadb nginx supervisor
echo "Zy4Panel installed. Edit ${PANEL_DIR}/.env, then run: php artisan config:cache"
