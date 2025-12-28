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
    # Spanish language support for LibreOffice
    libreoffice-lang-es \
    # Python for unoserver
    py3-pip \
    # Font packages
    font-liberation \
    font-dejavu \
    font-freefont \
    font-noto \
    font-opensans \
    fontconfig \
    ttf-liberation \
    ttf-dejavu \
    ttf-freefont \
    # MS Core fonts compatibility
    msttcorefonts-installer \
    # Install unoserver via pip
    && pip3 install --no-cache-dir --break-system-packages unoserver \
    # Update MS fonts \
    && update-ms-fonts \
    # Rebuild font cache
    && fc-cache -f -v \
    # Clean up
    && rm -rf /tmp/* /var/tmp/*

# Copy S6 overlay service for unoserver
COPY --chmod=755 docker/s6-overlay/ /etc/s6-overlay/

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
# Production Image
############################################

FROM base AS production

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY --chown=www-data:www-data . /var/www/html
