# 🚀 Guide de Déploiement - KIT HRM

## 📋 Table des Matières

1. [Déploiement Local (Développement)](#déploiement-local)
2. [Déploiement en Production (Hébergeur)](#déploiement-production)
3. [Configuration de la Base de Données](#configuration-database)
4. [Sécurité et Optimisation](#sécurité)
5. [Maintenance](#maintenance)

---

## 🏠 Déploiement Local (Développement)

### Prérequis

- PHP 8.2 ou supérieur
- Composer 2.x
- PostgreSQL 14+
- Node.js 18+ et npm
- Git

### 1. Cloner le Projet
```bash
git clone <url-du-repo>
cd kit_hrm
```

### 2. Installation des Dépendances
```bash
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
npm run build
```

### 3. Configuration de l'Environnement
```bash
# Copier le fichier .env
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 4. Configuration de la Base de Données

Éditez `.env` :
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kit_hrm
DB_USERNAME=postgres
DB_PASSWORD=kisinit2025
```

Créez la base de données :
```bash
# Connexion PostgreSQL
psql -U postgres

# Créer la base
CREATE DATABASE kit_hrm;
\q
```

### 5. Migrations et Seeders
```bash
# Exécuter les migrations
php artisan migrate

# Seeders (rôles, permissions, données de test)
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=DocumentCategorySeeder

# OU tout en une fois
php artisan migrate:fresh --seed
```

### 6. Créer un Utilisateur Admin
```bash
php artisan tinker
```
```php
$user = App\Models\User::create([
    'name' => 'Admin Principal',
    'email' => 'admin@kisinit237.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('admin');
exit
```

### 7. Configuration du Stockage
```bash
# Créer le lien symbolique pour storage
php artisan storage:link

# Créer les dossiers nécessaires
mkdir -p storage/app/public/documents
mkdir -p storage/app/public/payslips
mkdir -p storage/app/public/photos
```

### 8. Lancer le Serveur de Développement
```bash
# Terminal 1 : Serveur PHP
php artisan serve

# Terminal 2 : Vite (si en mode dev)
npm run dev
```

Application accessible sur : http://localhost:8000/admin

**Identifiants par défaut :**
- Email : admin@kisinit237.com
- Password : password

---

## 🌐 Déploiement en Production (Hébergeur)

### Prérequis Hébergeur

- VPS ou serveur partagé avec :
  - PHP 8.2+ (avec extensions : pdo, pgsql, mbstring, xml, bcmath, gd, zip)
  - PostgreSQL 14+
  - Composer
  - SSL/HTTPS
  - Minimum 2GB RAM

### Option A : Déploiement sur VPS (Ubuntu)

#### 1. Préparation du Serveur
```bash
# Mise à jour système
sudo apt update && sudo apt upgrade -y

# Installation PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-pgsql php8.2-mbstring \
    php8.2-xml php8.2-bcmath php8.2-gd php8.2-zip php8.2-curl \
    php8.2-intl php8.2-redis

# Installation PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Installation Nginx
sudo apt install -y nginx

# Installation Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installation Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 2. Configuration PostgreSQL
```bash
# Connexion PostgreSQL
sudo -u postgres psql

# Créer base et utilisateur
CREATE DATABASE hrm_chuy;
CREATE USER hrm_user WITH ENCRYPTED PASSWORD 'mot_de_passe_fort';
GRANT ALL PRIVILEGES ON DATABASE hrm_chuy TO hrm_user;
\q
```

#### 3. Déploiement de l'Application
```bash
# Créer le dossier
sudo mkdir -p /var/www/hrm_chuy
sudo chown -R $USER:$USER /var/www/hrm_chuy

# Cloner ou uploader le projet
cd /var/www/hrm_chuy
git clone <url-du-repo> .

# OU via FTP/SFTP : uploadez tous les fichiers

# Installation
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Permissions
sudo chown -R www-data:www-data /var/www/hrm_chuy
sudo chmod -R 755 /var/www/hrm_chuy
sudo chmod -R 775 /var/www/hrm_chuy/storage
sudo chmod -R 775 /var/www/hrm_chuy/bootstrap/cache
```

#### 4. Configuration .env Production
```env
APP_NAME="HRM CHUY"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hrm.chuy.cm

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hrm_chuy
DB_USERNAME=hrm_user
DB_PASSWORD=mot_de_passe_fort

# Sessions et cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail (configurez selon votre serveur SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=mot_de_passe_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@chuy.cm
MAIL_FROM_NAME="${APP_NAME}"
```

#### 5. Configuration Nginx

Créez `/etc/nginx/sites-available/hrm_chuy` :
```nginx
server {
    listen 80;
    server_name hrm.chuy.cm;
    root /var/www/hrm_chuy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

Activez le site :
```bash
sudo ln -s /etc/nginx/sites-available/hrm_chuy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 6. SSL avec Let's Encrypt
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d hrm.chuy.cm
```

#### 7. Finalisation
```bash
cd /var/www/hrm_chuy

# Migrations
php artisan migrate --force

# Seeders
php artisan db:seed --class=RolesAndPermissionsSeeder --force

# Optimisations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Lien storage
php artisan storage:link
```

### Option B : Hébergement Partagé (cPanel)

#### 1. Préparation Fichiers

Sur votre machine locale :
```bash
# Build assets
npm run build

# Archive
tar -czf hrm_chuy.tar.gz --exclude=node_modules --exclude=.git .
```

#### 2. Upload via cPanel

1. **File Manager** → Uploadez `hrm_chuy.tar.gz` dans `public_html`
2. Extrayez l'archive
3. Déplacez le contenu du dossier `public` vers `public_html`
4. Déplacez le reste dans un dossier parent (ex: `../hrm_app`)

#### 3. Configuration

Créez `.htaccess` dans `public_html` :
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Éditez `public/index.php` :
```php
// Changez les chemins
require __DIR__.'/../hrm_app/vendor/autoload.php';
$app = require_once __DIR__.'/../hrm_app/bootstrap/app.php';
```

#### 4. Base de Données via cPanel

1. **MySQL Databases** → Créez base `cpanel_hrm_chuy`
2. Créez utilisateur et donnez tous les privilèges
3. Importez via **phpMyAdmin** (si migration depuis PostgreSQL)

#### 5. Configuration .env

Via **File Manager**, éditez `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cpanel_hrm_chuy
DB_USERNAME=cpanel_user
DB_PASSWORD=mot_de_passe
```

#### 6. Commandes via Terminal SSH (si disponible)
```bash
cd public_html/hrm_app
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link
php artisan optimize
```

---

## 🔒 Sécurité et Optimisation

### Sécurité
```bash
# Permissions strictes
sudo chmod 644 .env
sudo chmod -R 755 storage bootstrap/cache

# Désactiver les fonctions dangereuses (php.ini)
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

# Firewall (UFW sur Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Optimisation
```bash
# OPcache (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

# Caches Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue Worker (pour jobs asynchrones)
php artisan queue:work --daemon
```

### Monitoring
```bash
# Logs
tail -f storage/logs/laravel.log

# Surveillance serveur
htop
```

---

## 🔧 Maintenance

### Mise à Jour
```bash
# Backup base de données
pg_dump hrm_chuy > backup_$(date +%Y%m%d).sql

# Pull changements
git pull origin main

# Mises à jour
composer install --no-dev
npm run build

# Migrations
php artisan migrate --force

# Nettoyage cache
php artisan optimize:clear
php artisan optimize
```

### Backup Automatique

Script `backup.sh` :
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/hrm_chuy"

# Base de données
pg_dump hrm_chuy > $BACKUP_DIR/db_$DATE.sql

# Fichiers storage
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz storage/app/public

# Nettoyage (garder 30 jours)
find $BACKUP_DIR -mtime +30 -delete
```

Cron (tous les jours à 2h) :
```bash
crontab -e
# Ajoutez :
0 2 * * * /path/to/backup.sh
```

---

## 📞 Support

En cas de problème :
- Logs : `storage/logs/laravel.log`
- Cache : Bouton "Vider le Cache" dans l'interface
- Base : `php artisan tinker` pour debugging

---

**Déployé avec ❤️ pour KISIN IT**