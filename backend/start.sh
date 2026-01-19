#!/bin/sh

# 启动PHP-FPM
php-fpm84 -D

# 启动Nginx
nginx -g "daemon off;"