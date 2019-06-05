FROM php:7.2-cli

RUN apt-get update \
 && apt-get install -y libmemcached-dev \
 && apt-get install -y zlib1g-dev \
 && apt-get install -y git \
 && yes '' | pecl install memcached \
 && echo 'extension=memcached.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install redis \
 && echo 'extension=redis.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install xdebug-2.6.0beta1 \
 && echo 'zend_extension=xdebug.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install mongodb \
 && echo 'extension=mongodb.so' >> /usr/local/etc/php/php.ini \
 && useradd -m ganesha

# soushi
USER ganesha
WORKDIR /home/ganesha
RUN mkdir .composer \
 && curl -Ss https://getcomposer.org/installer | php
COPY soushi.composer.json .composer/composer.json
RUN php composer.phar global install
ENV PATH $PATH:/home/ganesha/.composer/vendor/bin
