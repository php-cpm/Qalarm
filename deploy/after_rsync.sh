source /etc/profile

PHP_BIN="/usr/local/php/bin/php"
if [ ! -f $PHP_BIN ]; then
    PHP_BIN="/usr/bin/env php"
fi

# Artisan
$PHP_BIN artisan route:cache
#$PHP_BIN artisan queue:restart

# Grunt compile
cd public/angulr/ &&  sh gaea.sh refresh
