# 使用 PHP 8.2 FPM 為基底
FROM php:8.2-fpm

# 安裝必要擴充
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www

# 複製專案檔案
COPY . .

# 安裝 Laravel 套件
RUN composer install --no-dev --optimize-autoloader

# 權限設定
RUN chmod -R 775 storage bootstrap/cache

# Laravel 需要開啟的 port
EXPOSE 8000

# 啟動命令
CMD php artisan serve --host=0.0.0.0 --port=8000