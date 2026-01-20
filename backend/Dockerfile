# 使用官方Nginx镜像作为基础
FROM nginx:stable-alpine

# 安装PHP和必要的扩展
RUN apk add --no-cache php php-fpm php-pdo php-pdo_mysql php-mysqli php-curl php-json php-gd php-exif

# 复制Nginx配置文件
COPY nginx/conf.d/ /etc/nginx/conf.d/

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