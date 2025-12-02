FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install MongoDB extension (match your local XAMPP version)
RUN pecl install mongodb-1.19.3 \
    && docker-php-ext-enable mongodb

# Set working directory
WORKDIR /var/www/html

# Copy full project
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run composer install
RUN composer install --no-dev --optimize-autoloader

# Expose port 10000
EXPOSE 10000

# Run the server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
