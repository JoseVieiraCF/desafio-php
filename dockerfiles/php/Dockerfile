FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    zlib-dev \
    curl \
    shadow \
    mysql-client

RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-freetype \
    --with-webp && \
    docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache nodejs npm


WORKDIR /var/www

EXPOSE 9000