FROM php:8.0-apache
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update -y && apt-get install -y \
    libmariadb-dev \
    libc-client-dev \
    libkrb5-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure and install PHP extensions
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install mysqli imap

