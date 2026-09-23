# Gunakan image resmi PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install dependency sistem yang dibutuhkan oleh driver PostgreSQL (libpq)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install & aktifkan ekstensi PDO PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Set working directory di dalam container
WORKDIR /var/www/html

# Copy seluruh kode projek ke container
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
