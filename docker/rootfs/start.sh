#!/bin/ash

cd /var/www/backupdrop

echo ' [+] Starting php'
php-fpm7 &

chown -R nginx:nginx /var/www/ &

echo ' [+] Starting nginx'
mkdir -p /var/log/php-fpm/
touch /var/log/php-fpm/fpm-error.log
touch /var/log/nginx/backupdorp.access.log
touch /var/log/nginx/backupdorp.error.log
nginx


# Config

_buildConfig() {
    echo "<?php"

    echo "define('S3_BUCKET','${S3_BUCKET:-}');"
    echo "define('S3_ACCESS_KEY','${S3_ACCESS_KEY:-}');"
    echo "define('S3_SECRET_KEY','${S3_SECRET_KEY:-}');"
    echo "define('S3_ENDPOINT','${S3_ENDPOINT:-}');"

    echo "define('KEEP_N_BACKUPS',${KEEP_N_BACKUPS:-0});"
    echo "define('KEEP_N_DAYS',${KEEP_N_DAYS:-0});"
    echo "define('KEEP_N_GIGABYTES',${KEEP_N_GIGABYTES:-0});"

    echo "define('FTP_SSL',${FTP_SSL:-false});"
    echo "define('FTP_SERVER','${FTP_SERVER:-}');"
    echo "define('FTP_PORT',${FTP_PORT:-21});"
    echo "define('FTP_USER','${FTP_USER:-}');"
    echo "define('FTP_PASS','${FTP_PASS:-}');"
    echo "define('FTP_BASEDIR','${FTP_BASEDIR:-}');"
}


_buildConfig > config/config.inc.php

tail -n 1 -f /var/log/nginx/*.log