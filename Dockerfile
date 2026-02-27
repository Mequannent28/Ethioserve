# EthioServe - Self-Contained PHP + MariaDB for Render
FROM php:8.2-apache

# Install MariaDB server + PHP MySQL extensions
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite expires deflate \
    && rm -rf /var/lib/apt/lists/*

# Configure MariaDB for low memory usage (Render free tier = 512MB)
RUN printf "[mysqld]\n\
    bind-address=0.0.0.0\n\
    port=3306\n\
    socket=/var/run/mysqld/mysqld.sock\n\
    innodb_buffer_pool_size=32M\n\
    innodb_log_file_size=8M\n\
    max_connections=20\n\
    key_buffer_size=8M\n\
    query_cache_size=0\n\
    tmp_table_size=8M\n\
    max_heap_table_size=8M\n\
    skip-name-resolve\n\
    performance_schema=OFF\n\
    \n\
    [client]\n\
    socket=/var/run/mysqld/mysqld.sock\n" > /etc/mysql/mariadb.conf.d/99-render.cnf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Initialize MariaDB data directory
RUN mkdir -p /var/run/mysqld \
    && chown -R mysql:mysql /var/run/mysqld /var/lib/mysql \
    && chmod 777 /var/run/mysqld \
    && mysql_install_db --user=mysql --datadir=/var/lib/mysql 2>/dev/null || true

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN tr -d '\r' < /usr/local/bin/docker-entrypoint.sh > /usr/local/bin/entrypoint.sh \
    && mv /usr/local/bin/entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
