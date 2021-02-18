############################
# Install PHP Dependencies 
FROM composer:1.10 as vendor

WORKDIR /app
COPY database/ database/
COPY composer*.json ./

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist && \
    wget -O /app/php-fpm-healthcheck \
        https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/v0.5.0/php-fpm-healthcheck

############################
# Install Frontend dependency
# FROM node:12.18-buster-slim as frontend

# WORKDIR /app

# COPY package.json webpack.mix.js ./
# COPY resources/css ./resources/css
# COPY resources/js ./resources/js

# RUN apt update &&  \
#     apt install -y libpng-dev wget && \
#     npm install && \
#     npm run production

############################
# Run Laravel in PHP-FPM

FROM php:7.4-fpm-buster

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Install PHP extensions from list: https://gist.github.com/chronon/95911d21928cff786e306c23e7d1d3f3
# RUN docker-php-ext-install pdo_mysql zip exif pcntl && \
#    docker-php-ext-configure gd --with-freetype --with-jpeg && \
#    docker-php-ext-install gd

# Enable Health Check
ENV FCGI_STATUS_PATH "/health"
COPY --chown=www:www --from=vendor /app/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck && \
    set -xe && echo "pm.status_path = /health" >> /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/
# Copy vendor from composer
COPY --chown=www:www --from=vendor /app/vendor/ /var/www/vendor/
# Copy manifest that mapping all assets from frontend
# COPY --chown=www:www --from=frontend /app/mix-manifest.json /var/www/public/mix-manifest.json
# Copy existing application 
COPY --chown=www:www . /var/www

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 8000
CMD ["php-fpm"]
