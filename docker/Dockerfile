FROM php:8.2-apache

# Xdebugのインストール
RUN pecl install xdebug && docker-php-ext-enable xdebug

# php.iniファイルコピー
COPY php.ini /usr/local/etc/php/php.ini

# Apacheを有効化
CMD ["apache2-foreground"]
