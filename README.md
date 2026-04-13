# 🚀 Déploiement Rapide - KIT HRM

## ⚡ Installation Locale (5 minutes)
```bash
# 1. Cloner
git clone <repo> && cd kit_hrm

# 2. Installer
composer install && npm install && npm run build

# 3. Configurer
cp .env.example .env
php artisan key:generate

# 4. Base de données (éditez .env d'abord)
php artisan migrate:fresh --seed

# 5. Admin
php artisan tinker
>>> $u = User::create(['name'=>'Admin','email'=>'admin@kisinit237@gmail.com','password'=>bcrypt('password')]);
>>> $u->assignRole('admin');
>>> exit

# 6. Lancer
php artisan serve
```

**Accès :** http://localhost:8000/admin  
**Login :** admin@chuy.cm / password

---

## 🌍 Déploiement Production (VPS Ubuntu)
```bash
# 1. Serveur
sudo apt update && sudo apt install -y php8.2 php8.2-pgsql nginx postgresql composer

# 2. Base
sudo -u postgres psql
CREATE DATABASE hrm_chuy;
\q

# 3. Code
cd /var/www && git clone <repo> hrm_chuy
cd hrm_chuy
composer install --no-dev && npm run build

# 4. Config .env (IMPORTANT!)
nano .env  # Configurez DB et APP_URL

# 5. Setup
php artisan migrate --force
php artisan storage:link
php artisan optimize

# 6. Permissions
sudo chown -R www-data:www-data storage bootstrap/cache

# 7. Nginx + SSL
sudo certbot --nginx -d hrm.votredomaine.cm
```

**Voir DEPLOYMENT.md pour le guide complet**

---

## 🆘 Problèmes Courants

**Erreur permissions :**
```bash
sudo chmod -R 775 storage bootstrap/cache
```

**Cache bloqué :**
```bash
php artisan optimize:clear
```

**Base vide :**
```bash
php artisan migrate:fresh --seed
```