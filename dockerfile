# Используем базовый образ PHP с установленным FPM и расширениями
FROM php:8.3-fpm

# Устанавливаем зависимости
RUN apt update
RUN apt install -y libpng-dev zip unzip curl git iproute2 libpq-dev php8.3-zip
RUN docker-php-ext-install pdo pdo_pgsql gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копипуем файлы проекта в рабочую папку
WORKDIR /var/www
COPY . /var/www

# Настраиваем PHP для увеличения лимитов файлов
# Основные лимиты для загрузки файлов:
RUN echo 'upload_max_filesize = 256M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'post_max_size = 256M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'max_execution_time = 600' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'max_input_time = 600' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'max_file_uploads = 100' >> /usr/local/etc/php/conf.d/uploads.ini

RUN composer install

# Устанавливаем права на папку storage
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Настраиваем PHP-FPM для работы на всех интерфейсах
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Также настраиваем лимиты для PHP-FPM
RUN echo 'php_admin_value[upload_max_filesize] = 256M' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'php_admin_value[post_max_size] = 256M' >> /usr/local/etc/php-fpm.d/www.conf

# Запускаем PHP-FPM
CMD ["php-fpm"]
