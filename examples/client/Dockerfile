FROM php:7.4.1-cli

RUN apt-get update \
 && apt-get install -y libmemcached-dev \
 && apt-get install -y zlib1g-dev \
 && apt-get install -y git \
 && yes '' | pecl install memcached \
 && echo 'extension=memcached.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install redis \
 && echo 'extension=redis.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install xdebug-2.9.0 \
 && echo 'zend_extension=xdebug.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install mongodb \
 && echo 'extension=mongodb.so' >> /usr/local/etc/php/php.ini \
 && useradd -m ganesha

