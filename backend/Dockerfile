# 使用官方Nginx镜像作为基础
FROM nginx:stable-alpine

# 安装PHP和必要的扩展，确保安装php-fpm和正确版本
RUN apk add --no-cache php83 php83-fpm php83-pdo php83-pdo_mysql php83-mysqli php83-curl php83-json php83-gd php83-exif

# 复制Nginx配置文件
COPY nginx/conf.d/ /etc/nginx/conf.d/

# 修改PHP-FPM配置，确保监听在127.0.0.1:9000
RUN sed -i 's/listen = \/run\/php83-fpm.sock/listen = 127.0.0.1:9000/g' /etc/php83/php-fpm.d/www.conf
# 直接修改PHP-FPM运行用户为nginx
RUN sed -i 's/user = nobody/user = nginx/g' /etc/php83/php-fpm.d/www.conf
RUN sed -i 's/group = nobody/group = nginx/g' /etc/php83/php-fpm.d/www.conf

# 设置工作目录
WORKDIR /var/www/html

# 复制应用代码
COPY src/ /var/www/html/

# 设置权限
RUN chown -R nginx:nginx /var/www/html /var/log/nginx /var/cache/nginx

# 确保照片目录存在并设置正确权限
RUN mkdir -p /var/www/html/Photos /var/www/html/TheDeletePhotos
RUN chown -R nginx:nginx /var/www/html/Photos /var/www/html/TheDeletePhotos
RUN chmod -R 755 /var/www/html/Photos /var/www/html/TheDeletePhotos

# 暴露端口
EXPOSE 80

# 启动脚本
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]