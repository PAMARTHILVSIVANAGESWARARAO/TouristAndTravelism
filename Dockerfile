FROM php:8.2-cli

# Install system dependencies required for MongoDB SSL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Install MongoDB extension with SSL
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb

# Expose port
EXPOSE 10000

# Start server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
