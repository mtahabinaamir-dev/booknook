# BookNook on Render: Apache + PHP, SQLite database (no MySQL server needed)
FROM php:8.3-apache
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
COPY . /var/www/html/
RUN mkdir -p /var/www/booknook-data && chown -R www-data:www-data /var/www/html /var/www/booknook-data
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]
