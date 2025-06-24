FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# PHP 리포지토리 추가 및 시스템 업데이트
RUN apt-get update && apt-get install -y software-properties-common
RUN LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php

# 시스템 업데이트 및 필요한 패키지 설치
RUN apt-get update && apt-get install -y \
    nginx \
    php8.1-fpm \
    php8.1-mysql \
    php8.1-xml \
    php8.1-mbstring \
    php8.1-curl \
    php8.1-zip \
    php8.1-gd \
    php8.1-cli \
    php8.1-common \
    php8.1-json \
    php8.1-opcache \
    php8.1-readline \
    php8.1-sqlite3 \
    php8.1-bcmath \
    php8.1-intl \
    php8.1-xmlrpc \
    php8.1-soap \
    php8.1-xsl \
    php8.1-ldap \
    php8.1-imap \
    php8.1-tidy \
    php8.1-dev \
    composer \
    supervisor \
    curl \
    vim \
    && rm -rf /var/lib/apt/lists/*

# PHP 설정 수정 (FPM)
RUN echo "max_execution_time = 0" >> /etc/php/8.1/fpm/php.ini && \
    echo "max_input_time = 0" >> /etc/php/8.1/fpm/php.ini && \
    echo "memory_limit = 1G" >> /etc/php/8.1/fpm/php.ini && \
    echo "post_max_size = 200M" >> /etc/php/8.1/fpm/php.ini && \
    echo "upload_max_filesize = 200M" >> /etc/php/8.1/fpm/php.ini && \
    echo "max_file_uploads = 50" >> /etc/php/8.1/fpm/php.ini && \
    echo "default_socket_timeout = 300" >> /etc/php/8.1/fpm/php.ini

# PHP 설정 수정 (CLI)
RUN echo "max_execution_time = 0" >> /etc/php/8.1/cli/php.ini && \
    echo "max_input_time = 0" >> /etc/php/8.1/cli/php.ini && \
    echo "memory_limit = 1G" >> /etc/php/8.1/cli/php.ini && \
    echo "post_max_size = 200M" >> /etc/php/8.1/cli/php.ini && \
    echo "upload_max_filesize = 200M" >> /etc/php/8.1/cli/php.ini

# PHP-FPM 풀 설정 수정
RUN echo "request_terminate_timeout = 300" >> /etc/php/8.1/fpm/pool.d/www.conf && \
    echo "pm.max_requests = 100" >> /etc/php/8.1/fpm/pool.d/www.conf

# Nginx 설정
COPY conf/nginx.conf /etc/nginx/conf.d/default.conf

# Supervisor 설정
COPY conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 웹 디렉토리 생성
RUN mkdir -p /var/www/html

# 권한 설정
RUN chown -R www-data:www-data /var/www/html

# 포트 노출
EXPOSE 80 443

# Supervisor로 서비스 시작
CMD ["/usr/bin/supervisord"]