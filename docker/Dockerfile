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
RUN sed -i 's/nobody/nginx/g' /etc/php83/php-fpm.d/www.conf
RUN sed -i 's/E_ALL \& ~E_DEPRECATED \& ~E_STRICT/E_ALL \& ~E_DEPRECATED \& ~E_STRICT \& ~E_NOTICE \& ~E_WARNING/g' /etc/php83/php.ini

# web interface stuff
WORKDIR /var/www/backupdrop/web/lib
RUN composer install
WORKDIR /var/www/backupdrop/

EXPOSE 80
VOLUME /var/www/backupdrop/data


#CMD ["/bin/ash"]
ENTRYPOINT ["/etc/start.sh"]