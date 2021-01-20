FROM alpine:edge

RUN apk add --no-cache redis pwgen sudo socat wget curl git nginx \
php7-ctype php7-ftp php7-simplexml php7 php7-phar php7-curl php7-openssl php7-mbstring php7-json php7-dom php7-fpm

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 
RUN mkdir -p /var/www/backupdrop
WORKDIR /var/www/backupdrop

COPY ./ .

RUN find /var/www/

# ADD rootfs/start.sh /etc/start.sh
RUN cp /var/www/backupdrop/docker/rootfs/start.sh /etc/start.sh
RUN chmod +x /etc/start.sh

# nginx stuff
RUN cp /var/www/backupdrop/docker/rootfs/nginx.conf /etc/nginx/conf.d/default.conf
RUN mkdir -p /run/nginx
RUN mkdir -p /var/log/nginx

# php stuff
RUN cp /var/www/backupdrop/docker/rootfs/php7/* /etc/php7/.

# web interface stuff
WORKDIR /var/www/backupdrop/web/lib
RUN composer install
WORKDIR /var/www/backupdrop/

EXPOSE 80
VOLUME /var/www/backupdrop/data


#CMD ["/bin/ash"]
ENTRYPOINT ["/etc/start.sh"]