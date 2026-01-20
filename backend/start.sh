#!/bin/sh

# 启动PHP-FPM
php-fpm83 -D

# 启动Nginx
nginx -g "daemon off;"