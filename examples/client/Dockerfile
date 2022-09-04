FROM php:8.0-cli

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
 && yes '' | pecl install xdebug-3.1.5 \
 && echo 'zend_extension=xdebug.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install mongodb \
 && echo 'extension=mongodb.so' >> /usr/local/etc/php/php.ini \
 && yes '' | pecl install apcu \
 && echo 'extension=apcu.so' >> /usr/local/etc/php/php.ini \
 && echo 'apc.enable_cli=1' >> /usr/local/etc/php/php.ini \
 && useradd -m ganesha \
 && rm -rf /var/lib/apt/lists/* \
 && rm -rf /var/cache/apt/* \
 # Install composer
 # https://getcomposer.org/download/
 && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
 && php composer-setup.php \
 && php -r "unlink('composer-setup.php');" \
 && mv composer.phar /usr/local/bin/composer

