FROM php:8.2-apache

# 画像処理に必要なパッケージをインストールしてGDをビルド・インストール
RUN apt-get update && apt-get install -y \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) gd

# URI自動生成に必要なパッケージをインストール
RUN docker-php-ext-install bcmath

# Xdebugのインストール
RUN pecl install xdebug && docker-php-ext-enable xdebug

# php.iniファイルコピー
COPY php.ini /usr/local/etc/php/php.ini

# Apacheを有効化
CMD ["apache2-foreground"]
