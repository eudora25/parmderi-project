FROM ubuntu:20.04

RUN apt-get update && apt-get install -y software-properties-common
RUN LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php


RUN apt-get update -y && apt-get install -y nginx supervisor curl vim
RUN apt-get update -y && apt-get install -y php8.1 \
    php8.1-fpm \
    php8.1-common \
    php8.1-mysql \
    php8.1-gmp \
    php8.1-ldap \
    php8.1-curl \
    php8.1-intl \
    php8.1-mbstring \
    php8.1-xmlrpc \
    php8.1-gd \
    php8.1-bcmath \
    php8.1-xml \
    php8.1-cli \
    php8.1-memcache \
    php8.1-redis \
    php8.1-zip

# PHP 설정 업데이트
RUN echo "upload_max_filesize = 100M" >> /etc/php/8.1/fpm/php.ini && \
    echo "post_max_size = 100M" >> /etc/php/8.1/fpm/php.ini && \
    echo "memory_limit = 256M" >> /etc/php/8.1/fpm/php.ini && \
    echo "max_execution_time = 300" >> /etc/php/8.1/fpm/php.ini && \
    echo "max_input_time = 300" >> /etc/php/8.1/fpm/php.ini

# PHP-FPM 설정 수정
RUN sed -i 's/listen = \/run\/php\/php8.1-fpm.sock/listen = \/var\/run\/php\/php8.1-fpm.sock/g' /etc/php/8.1/fpm/pool.d/www.conf && \
    sed -i 's/;listen.owner = www-data/listen.owner = www-data/g' /etc/php/8.1/fpm/pool.d/www.conf && \
    sed -i 's/;listen.group = www-data/listen.group = www-data/g' /etc/php/8.1/fpm/pool.d/www.conf && \
    sed -i 's/;listen.mode = 0660/listen.mode = 0660/g' /etc/php/8.1/fpm/pool.d/www.conf

# 디렉토리 권한 설정
RUN mkdir -p /var/run/php && \
    chown www-data:www-data /var/run/php

#Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./conf/nginx.conf /etc/nginx/conf.d/default.conf
COPY ./conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf


EXPOSE 80
EXPOSE 443
#EXPOSE 22
#EXPOSE 9000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]