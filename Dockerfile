FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    supervisor

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    gd \
    bcmath \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Create a dummy .env so artisan commands work during build
RUN cp .env.example .env

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm ci && npm run build

# Generate app key
RUN php artisan key:generate

RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

CMD ["/bin/sh", "-c", "php artisan config:cache && php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]