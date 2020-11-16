FROM php:7.4-apache as backend
RUN apt-get -y update && \
    apt-get install -y curl zip git libicu-dev libonig-dev libxml2-dev librabbitmq-dev libssh-dev && \
    pecl install apcu amqp redis && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl opcache mbstring pdo_mysql bcmath soap sockets && \
    docker-php-ext-enable apcu && \
    docker-php-ext-enable amqp && \
    docker-php-ext-enable redis && \
    apt-get clean && rm -rf /tmp/pear

ARG ENABLE_XDEBUG=0

RUN if [ "$ENABLE_XDEBUG" -eq "1" ]; then \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    echo "xdebug.remote_enable = 1" > /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_connect_back = 1" >> /usr/local/etc/php/conf.d/xdebug.ini ; \
 fi

RUN a2enmod rewrite && \
    rm -rf /var/www/html && \
    ln -s /app/public /var/www/html

RUN echo "memory_limit = 512M" >> /usr/local/etc/php/php.ini && \
    echo "max_execution_time = 120" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 64M" >> /usr/local/etc/php/php.ini && \
    echo "upload_max_filesize = 64M" >> /usr/local/etc/php/php.ini

ENV COMPOSER_ALLOW_SUPERUSER=1;
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require hirak/prestissimo --no-plugins --no-scripts --no-progress

WORKDIR /app

FROM node:14-alpine as assets
WORKDIR /build
COPY package.json yarn.lock webpack.config.js tsconfig.json ./
RUN apk --no-cache --update --virtual build-dependencies add \
    python \
    make \
    g++ \
    && yarn install \
    && apk del build-dependencies
COPY assets/ assets/
RUN yarn build --no-progress

FROM backend as app
ENV COMPOSER_ALLOW_SUPERUSER=1;
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-progress
COPY . ./
COPY --from=assets /build/public/build public/build/
RUN composer dump-autoload --no-dev --optimize && composer dump-env prod && php bin/console assets:install && php bin/console cache:clear
RUN if [ -d var/ ]; then mkdir -p var/cache/ && chmod -R 777 var/; fi
