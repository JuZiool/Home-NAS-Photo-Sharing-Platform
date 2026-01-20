#!/bin/sh

# 启动PHP-FPM 83
php83-fpm -D

# 启动Nginx
nginx -g "daemon off;"