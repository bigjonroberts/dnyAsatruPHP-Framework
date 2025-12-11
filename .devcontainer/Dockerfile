FROM mcr.microsoft.com/devcontainers/php:8.3

# Install development libraries for PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmariadb-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql
