#!/bin/sh

# 修改PHP-FPM运行用户为nginx
sed -i 's/user = nobody/user = nginx/g' /etc/php83/php-fpm.d/www.conf
sed -i 's/group = nobody/group = nginx/g' /etc/php83/php-fpm.d/www.conf

# 确保目录权限正确
chown -R nginx:nginx /var/www/html/Photos /var/www/html/TheDeletePhotos
chmod -R 755 /var/www/html/Photos /var/www/html/TheDeletePhotos

# 启动PHP-FPM
php-fpm83 -D

# 启动Nginx
nginx -g "daemon off;"