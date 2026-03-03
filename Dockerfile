FROM php:8.0-apache
WORKDIR /var/www/html
RUN apt-get update -y && apt-get install -y libmariadb-dev libc-client-dev libkrb5-dev
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install mysqli imap
