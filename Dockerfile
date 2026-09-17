# Image PHP 8.2 avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Autoriser .htaccess (AllowOverride All)
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copier le contenu du projet dans le répertoire web Apache
COPY invitation-yvann-immaculee/ /var/www/html/

# Créer le dossier uploads avec les bonnes permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Render utilise le port 10000 par défaut (variable $PORT = 10000)
# On configure Apache pour écouter sur ce port au démarrage
CMD bash -c "sed -i \"s/Listen 80/Listen \${PORT:-10000}/g\" /etc/apache2/ports.conf && sed -i \"s/*:80>/*:\${PORT:-10000}>/g\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"
