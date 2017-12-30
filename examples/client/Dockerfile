FROM php:7.2-cli

RUN apt-get update \
 && apt-get install -y libmemcached-dev \
 && apt-get install -y zlib1g-dev \
 && yes '' | pecl install memcached \
 && echo 'extension=memcached.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install redis \
 && echo 'extension=redis.so' >> /usr/local/etc/php/php.ini \
 && useradd -m ganesha
