FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install zip mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/generated /var/www/html/exams \
    && chown -R www-data:www-data /var/www/html/generated /var/www/html/exams \
    && chmod -R 775 /var/www/html/generated /var/www/html/exams

EXPOSE 80