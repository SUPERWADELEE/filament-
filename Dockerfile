# 使用 PHP 8.3 FPM 為基底
FROM php:8.3-fpm

# 安裝必要擴充
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli pdo_pgsql pgsql mbstring zip gd intl
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


