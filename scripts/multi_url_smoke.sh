q#!/usr/bin/env bash
set -euo pipefail

# install mysqli if missing, start apache, hit several URLs and print logs
apt-get update -y >/dev/null || true
apt-get install -y default-libmysqlclient-dev build-essential >/dev/null 2>&1 || true
docker-php-ext-install mysqli >/dev/null 2>&1 || true

apache2ctl start >/dev/null 2>&1 || true
sleep 1
for u in "/" "/wp-login.php" "/wp-admin/" "/?doing_wp_cron" "/wp-json/"; do
  echo "REQUEST: $u"
  http_code=$(curl -s -o /tmp/body -w "%{http_code}" "http://127.0.0.1$u" || true)
  echo "HTTP_CODE:$http_code"
  echo '--- container apache error log ---'
  tail -n 200 /var/log/apache2/error.log || true
  echo '--- container php error log ---'
  tail -n 200 wp-content/php-error.log 2>/dev/null || true
  echo "------"
done

apache2ctl stop >/dev/null 2>&1 || true
