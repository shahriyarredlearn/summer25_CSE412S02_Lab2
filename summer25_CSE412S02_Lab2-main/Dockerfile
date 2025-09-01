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
RUN echo "display_errors = On" > /usr/local/etc/php/conf.d/display_errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/display_errors.ini

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure Apache to handle CORS
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    Header set Access-Control-Allow-Origin "*"\n\
    Header set Access-Control-Allow-Methods "GET,POST,OPTIONS,DELETE,PUT"\n\
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"\n\
</Directory>' > /etc/apache2/conf-available/cors.conf

RUN a2enconf cors

# Copy the database setup script
COPY setup-database.php /var/www/html/setup-database.php

# Expose port 80
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]

<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database setup...\n";

try {
    $host = 'db';
    $dbname = 'filerepository';
    $user = 'dbuser';
    $pass = 'dbpassword';
    
    echo "Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database created/verified\n";
    
    // Switch to the database
    $pdo->exec("USE $dbname");
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Users table created\n";
    
    // Create files table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        file_size BIGINT NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (user_email) REFERENCES users(email)
    )");
    echo "Files table created\n";
    
    // Create admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $adminEmail = 'admin@example.com';
        $adminPass = password_hash('Admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$adminEmail, $adminPass]);
        echo "Admin user created (admin@example.com / Admin123)\n";
    }
    
    echo "Database setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: filerepository
      MYSQL_USER: dbuser
      MYSQL_PASSWORD: dbpassword
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

volumes:
  mysql_data: