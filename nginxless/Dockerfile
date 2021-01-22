FROM php:fpm-alpine

RUN apk add --no-cache libstdc++ ${PHPIZE_DEPS} \
 && pecl install swoole \
 && docker-php-ext-enable swoole \
 && apk del ${PHPIZE_DEPS}

COPY --from=composer /usr/bin/composer /usr/bin/composer
ADD composer.json /srv/
ADD composer.lock /srv/
RUN cd /srv/ && composer install --prefer-dist --no-dev --optimize-autoloader

ADD server.php /srv/
ADD docker-php-entrypoint /usr/local/bin/
CMD ["php", "/srv/server.php"]
