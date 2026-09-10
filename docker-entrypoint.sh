#!/bin/bash
set -e

# Railway injecte la variable PORT — Apache doit écouter sur ce port
PORT="${PORT:-80}"

echo "Démarrage Apache sur le port $PORT..."

# Remplacer le port dans la config Apache
sed -i "s|Listen 80|Listen ${PORT}|g" /etc/apache2/ports.conf
sed -i "s|:80>|:${PORT}>|g" /etc/apache2/sites-available/000-default.conf
sed -i "s|:80>|:${PORT}>|g" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true

# Démarrer Apache en foreground
exec apache2-foreground
