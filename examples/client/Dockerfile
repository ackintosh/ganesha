FROM php:7.4-cli

RUN apt-get update \
 && apt-get install --no-install-recommends -y git \
 && apt-get install --no-install-recommends -y libmemcached-dev \
 && apt-get install --no-install-recommends -y zlib1g-dev \
 && apt-get install --no-install-recommends -y unzip \
 && apt-get install --no-install-recommends -y libzip-dev \
 && apt-get install --no-install-recommends -y git \
 && yes '' | pecl install zip \
 && echo 'extension=zip.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install memcached \
 && echo 'extension=memcached.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install redis \
 && echo 'extension=redis.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install xdebug-2.9.0 \
 && echo 'zend_extension=xdebug.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install mongodb \
 && echo 'extension=mongodb.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install apcu \
 && echo 'extension=apcu.so' >> /usr/local/etc/php/php.ini \
 && echo 'apc.enable_cli=1' >> /usr/local/etc/php/php.ini \
 && useradd -m ganesha \
 && rm -rf /var/lib/apt/lists/* \
 && rm -rf /var/cache/apt/*

