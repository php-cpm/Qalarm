PHP_BIN="/usr/local/php/bin/php"
if [ ! -f $PHP_BIN ]; then
    PHP_BIN="/usr/bin/env php"
fi

# Composer
#$PHP_BIN composer.phar install --no-dev

# Artisan
#$PHP_BIN artisan optimize --force
#$PHP_BIN artisan optimize --force --portable
