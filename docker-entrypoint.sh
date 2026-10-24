#!/bin/sh
# Render gives the port in $PORT; point Apache at it, then start Apache
sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
