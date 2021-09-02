FROM php:7.4-fpm
# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
RUN pecl install redis-5.3.1 && docker-php-ext-enable redis

# Copy vendor from composer
#COPY --chown=www:www --from=vendor /app/vendor/ /var/www/vendor/
# Copy manifest that mapping all assets from frontend
# COPY --chown=www:www --from=frontend /app/mix-manifest.json /var/www/public/mix-manifest.json
# Copy existing application 
COPY --chown=www:www . /var/www

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
