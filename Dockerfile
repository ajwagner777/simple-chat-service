FROM php:8.3-fpm-alpine

WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    sqlite \
    sqlite-dev \
    libpq-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    supervisor \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql pdo_mysql mbstring xml bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup SQLite database
RUN mkdir -p /var/www/database \
    && touch /var/www/database/database.sqlite \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
