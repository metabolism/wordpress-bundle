FROM node:16 AS node
FROM composer:2.5 AS composer
FROM php:8.2-fpm

# Set the working directory
WORKDIR /var/www/html

# Update package lists
RUN apt-get update

# Install required dependencies
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev curl git zip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd

# Copy Composer bin
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Allow Composer to run as superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy installation files
COPY composer.* ./

# Run Composer to install dependencies
RUN composer install --prefer-dist --no-dev --no-interaction

# Run Composer to update dependencies
RUN composer update --prefer-dist --no-dev --no-interaction

COPY . /var/www/html

# Optimizing Composer autoloader for better performance
RUN composer dump-autoload --optimize

# Copy Node bin
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Install app dependencies
RUN npm install

# Create build folder
RUN mkdir public/build
RUN chmod -R 0777 public/build

# Run npm run build during the build process
RUN npm run build