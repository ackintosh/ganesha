php -v
php -v | cut -d ' ' -f 2 
php -v | cut -d ' ' -f 2 | head -n 1

if [ "$(php -v | cut -d ' ' -f 2 | head -n 1)" == '5.6.40' ]; then
	printf "\n" | pecl install memcached-2.2.0
fi
