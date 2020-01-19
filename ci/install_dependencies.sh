#!/bin/bash -eu

php --version | cut -d ' ' -f 2 | head -n 1 | grep -e '^7\.[12]\.'

if [ $? = 0 ]; then
  yes '' | pecl install memcached-3.1.5
  yes '' | pecl install redis-5.1.0
  yes '' | pecl install mongodb-1.6.0
else
  phpenv config-add .travis.php.ini
fi
