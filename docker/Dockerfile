############################################
# Base Image
############################################
FROM serversideup/php:8.5-fpm-nginx-alpine-v4.2.1 AS base

USER root

# Install LibreOffice, unoserver, Spanish language support, and fonts
RUN apk add --no-cache \
    # LibreOffice packages \
    libreoffice \
    libreoffice-writer \
    libreoffice-calc \
    libreoffice-impress \
    libreoffice-draw \
    # Spanish language support for LibreOffice \
    libreoffice-lang-es \
    # Python for unoserver \
    py3-pip \
    # Font packages \
    font-liberation \
    font-dejavu \
    font-freefont \
    font-noto \
    font-opensans \
    fontconfig \
    ttf-liberation \
    ttf-dejavu \
    ttf-freefont \
    # MS Core fonts compatibility \
    msttcorefonts-installer \
    # Install unoserver via pip \
    && pip3 install --no-cache-dir --break-system-packages unoserver \
    # Update MS fonts \
    && update-ms-fonts \
    # Rebuild font cache \
    && fc-cache -f -v \
    # Clean up \
    && rm -rf /tmp/* /var/tmp/*

# Copy S6 overlay service for unoserver
COPY --chmod=755 docker/etc/s6-overlay/ /etc/s6-overlay/

USER www-data

############################################
# Development Image
############################################
FROM base AS development

USER root

ARG USER_ID
ARG GROUP_ID

# Use the build arguments to change the UID
# and GID of www-data while also changing
# the file permissions for NGINX
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    \
    # Update the file permissions to match the new UID/GID \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID

USER www-data

############################################
# Composer Dependencies (Production)
############################################
FROM base AS composer-prod

USER root

COPY composer.json composer.lock symfony.lock /var/www/html/

# Install production dependencies
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    && composer clear-cache \
    && rm -rf /root/.composer

############################################
# Production Image
############################################
FROM base AS production

ARG UPLOAD_MAX_SIZE=100M

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SHOW_WELCOME_MESSAGE=false \
    HEALTHCHECK_PATH="/health" \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_REVALIDATE_FREQ=0 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=16 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_MEMORY_LIMIT=512M \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_REALPATH_CACHE_TTL=600 \
    UPLOAD_MAX_SIZE=$UPLOAD_MAX_SIZE \
    PHP_UPLOAD_MAX_FILE_SIZE=$UPLOAD_MAX_SIZE \
    PHP_POST_MAX_SIZE=$UPLOAD_MAX_SIZE \
    PHP_DISPLAY_ERRORS=Off \
    NGINX_FASTCGI_BUFFERS="16 16k" \
    NGINX_FASTCGI_BUFFER_SIZE=32k


USER root

# Copy production PHP and Nginx configurations
COPY --chown=www-data:www-data docker/etc/php/zzz-extra-php.ini /usr/local/etc/php/conf.d/
COPY --chown=www-data:www-data docker/etc/nginx/extra-nginx.conf /etc/nginx/conf.d/

USER www-data

# Copy composer dependencies from composer stage
COPY --from=composer-prod --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor

# Copy application code
COPY --chown=www-data:www-data bin /var/www/html/bin
COPY --chown=www-data:www-data config /var/www/html/config
COPY --chown=www-data:www-data public /var/www/html/public
COPY --chown=www-data:www-data src /var/www/html/src
COPY --chown=www-data:www-data composer.json composer.lock symfony.lock /var/www/html/

# Rebuild autoloader
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Create necessary directories with proper permissions
USER root
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html/var \
    && chmod -R 775 /var/www/html/var

USER www-data

# Dump environment for production (use real env vars only) and warm up cache
RUN composer dump-env prod --empty \
    && php bin/console cache:clear  \
    && php bin/console cache:warmup
