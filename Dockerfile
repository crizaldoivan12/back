FROM php:8.2-apache

WORKDIR /var/www/html

# System packages and PHP extensions commonly needed by Laravel.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    netcat-openbsd \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    bcmath \
    exif \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
