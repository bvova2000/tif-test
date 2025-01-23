FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libcurl4-openssl-dev pkg-config libssl-dev

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN docker-php-ext-install pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]