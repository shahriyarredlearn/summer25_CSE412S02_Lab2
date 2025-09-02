FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip

# Enable Apache modules
RUN a2enmod rewrite

# Configure PHP
RUN echo "display_errors = Off" > /usr/local/etc/php/conf.d/display_errors.ini \
    && echo "error_reporting = 0" >> /usr/local/etc/php/conf.d/display_errors.ini \
    && echo "session.save_handler = files" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.save_path = /tmp" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.cookie_secure = 0" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.cookie_samesite = Lax" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.gc_maxlifetime = 7200" >> /usr/local/etc/php/conf.d/session.ini

# Create session directory and set permissions
RUN mkdir -p /tmp/sessions && chmod 777 /tmp/sessions

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]