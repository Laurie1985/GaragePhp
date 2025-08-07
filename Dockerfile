# Utiliser une image php oficielle avec Apache
FROM php:8.4-apache

# Installer les dépendances et bibliothèques nécessaires
RUN apt-get update && apt-get install -y && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ajouter ServerName dans la configuration d'Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
# RUN sed -i '/<VirtualHost \*:80>/a \    ServerName localhost' /etc/apache2/sites-available/000-default.conf

# Activer le mod_rewrite d'Apache pour les URLs
RUN a2enmod rewrite

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de dépendances et les installer
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist

# Copier le reste du code de l'application
COPY . .

# Executer le dump de l'autoloader de composer (pour les performances)
RUN composer dump-autoload --optimize

# Changer propriétaires des fichiers afin de donner le droit au serveur d'écrire dans les fichiers (EX: logs)
RUN mkdir -p storage/logs && \
    chown -R www-data:www-data /var/www/html/storage