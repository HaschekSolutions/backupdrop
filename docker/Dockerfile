FROM alpine:3.20

RUN apk add --no-cache pwgen sudo socat wget curl git nginx \
php83-ctype php83-ftp php83-simplexml php83 php83-phar php83-curl php83-openssl php83-mbstring php83-json php83-dom php83-fpm age

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 
RUN mkdir -p /var/www/backupdrop
WORKDIR /var/www/backupdrop

COPY ./ .

RUN find /var/www/

# ADD rootfs/start.sh /etc/start.sh
RUN cp /var/www/backupdrop/docker/rootfs/start.sh /etc/start.sh
RUN chmod +x /etc/start.sh

# nginx stuff
ADD docker/rootfs/nginx.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /run/nginx
RUN mkdir -p /var/log/nginx

# php stuff
RUN sed -i 's/nobody/backupdrop/g' /etc/php83/php-fpm.d/www.conf
RUN sed -i 's/group = nobody/group = backupdrop/g' /etc/php83/php-fpm.d/www.conf
RUN sed -i 's/E_ALL \& ~E_DEPRECATED \& ~E_STRICT/E_ALL \& ~E_DEPRECATED \& ~E_STRICT \& ~E_NOTICE \& ~E_WARNING/g' /etc/php83/php.ini
# disable upload file size limit
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 0/g' /etc/php83/php.ini
RUN sed -i 's/post_max_size = 8M/post_max_size = 0/g' /etc/php83/php.ini
# Fix PHP-FPM log directory
RUN mkdir -p /var/log/php83 && chmod 755 /var/log/php83

# web interface stuff
WORKDIR /var/www/backupdrop/web/lib
RUN composer install
WORKDIR /var/www/backupdrop/

# Create non-root user and set permissions
RUN addgroup -g 1000 backupdrop && \
    adduser -D -u 1000 -G backupdrop backupdrop && \
    chown -R backupdrop:backupdrop /var/www/backupdrop && \
    chown -R backupdrop:backupdrop /var/log/nginx && \
    chown -R backupdrop:backupdrop /var/log/php83 && \
    chown -R backupdrop:backupdrop /var/lib/nginx && \
    chown -R backupdrop:backupdrop /run/nginx && \
    mkdir -p /var/log/php-fpm && \
    chown -R backupdrop:backupdrop /var/log/php-fpm

# Switch to non-root user
USER backupdrop

EXPOSE 8080
VOLUME /var/www/backupdrop/data

ENTRYPOINT ["/etc/start.sh"]