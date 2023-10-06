FROM php:8.2-apache

RUN  apt-get update && \
      apt-get upgrade -y

RUN apt-get install nano -y
RUN apt-get install --no-install-recommends -y git nano vim curl jq unzip zip gnupg automake patch gcc make autoconf libc-dev pkg-config default-mysql-client sendmail openssh-client rsync imagemagick ghostscript
RUN apt-get install --no-install-recommends -y zlib1g-dev libzip-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev libxml2-dev libxslt-dev libmagickwand-dev  gettext libgmp-dev
RUN docker-php-ext-configure bcmath && \
    docker-php-ext-configure calendar && \
    docker-php-ext-configure exif && \
    docker-php-ext-configure gettext && \
    docker-php-ext-configure gmp && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure opcache && \
    docker-php-ext-configure pcntl && \
    docker-php-ext-configure pdo_mysql && \
    docker-php-ext-configure shmop && \
    docker-php-ext-configure soap && \
    docker-php-ext-configure sockets && \
    docker-php-ext-configure sysvmsg && \
    docker-php-ext-configure sysvsem && \
    docker-php-ext-configure sysvshm && \
    docker-php-ext-configure xsl && \
    docker-php-ext-configure zip && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install calendar && \
    docker-php-ext-install exif && \
    docker-php-ext-install gettext && \
    docker-php-ext-install gmp && \
    docker-php-ext-install intl && \
    docker-php-ext-install opcache && \
    docker-php-ext-install pcntl && \
    docker-php-ext-install shmop && \
    docker-php-ext-install soap && \
    docker-php-ext-install sockets && \
    docker-php-ext-install sysvmsg && \
    docker-php-ext-install sysvsem && \
    docker-php-ext-install sysvshm && \
    docker-php-ext-install xsl && \
    docker-php-ext-install zip && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    pecl install imagick && \
    docker-php-ext-enable imagick

RUN docker-php-ext-install pdo_mysql

# APACHE
RUN a2enmod alias headers rewrite expires


COPY 000-default.conf /etc/apache2/sites-enabled/000-default.conf


# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer --2

# # Install XDebug
# ARG WITH_XDEBUG=false
# RUN if [ $WITH_XDEBUG = "true" ] ; then  \
#       pecl install xdebug-3.1.2 && pecl channel-update pecl.php.net && docker-php-ext-enable xdebug; \
#       echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.iniOLD;  \
#     fi

# ADD xdebug.ini /usr/local/etc/php/conf.d/custom-xdebug.ini

COPY php.ini /usr/local/etc/php
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "ServerName xdebug.local" >> /etc/apache2/apache2.conf
EXPOSE 80

# Dockerfile
ARG USER=docker
ARG UID=1000
ARG GID=1000
# default password for user
ARG PW=docker
RUN useradd -m ${USER} --uid=${UID} && echo "${USER}:${PW}" | \
      chpasswd
RUN adduser ${USER} sudo
USER ${UID}:${GID}
WORKDIR /var/www/html