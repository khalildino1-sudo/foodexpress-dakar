# 🚀 Déploiement FoodExpress Dakar sur Render

Guide complet pour héberger FoodExpress Dakar sur la plateforme **Render.com** avec Blueprint et Docker.

---

## 📋 Prérequis

- Compte Render (https://render.com)
- Repository GitHub contenant ce projet
- Identifiants Gmail SMTP (pour les emails)

---

## 🔧 Étape 1 : Configuration Initiale sur Render

### 1.1 Connecter votre repository GitHub

1. Allez sur **Render Dashboard** → **Blueprints**
2. Cliquez sur **+ New Blueprint Instance**
3. Sélectionnez votre repository `khalildino1-sudo/foodexpress-dakar`
4. Render détectera automatiquement le fichier `render.yaml`

### 1.2 Architecture Docker

L'application utilise un **Dockerfile** pour PHP 8.2 avec les dépendances nécessaires :
- Image de base : `php:8.2-cli`
- Serveur : PHP built-in server sur port 8000
- Uploads : Répertoires créés automatiquement à la construction

### 1.3 Configurer les Variables d'Environnement

Après le déploiement initial, allez dans **Environment** → **Environment Variables** et remplissez :

#### Email (Gmail SMTP) - **OBLIGATOIRE**
```
SMTP_USER = votre_email@gmail.com
SMTP_PASS = votre_app_password
SMTP_FROM_EMAIL = votre_email@gmail.com
```

#### Base de Données (optionnel si DB externe)
```
DB_HOST = votre_host_mysql
DB_PORT = 3306
DB_NAME = foodexpress_dakar
DB_USER = foodexpress_user
DB_PASS = votre_password
```

**Comment générer un Gmail App Password :**
1. Allez sur https://myaccount.google.com/apppasswords
2. Sélectionnez **Mail** et **Windows Computer** (ou votre appareil)
3. Copiez le mot de passe généré
4. Collez-le dans `SMTP_PASS`

---

## 🗄️ Étape 2 : Configuration de la Base de Données

### Option A : Utiliser PlanetScale (MySQL compatible)

1. Créez un compte sur https://planetscale.com
2. Créez une base `foodexpress_dakar`
3. Récupérez les identifiants de connexion
4. Dans Render Dashboard, configurez :
   - `DB_HOST` = votre host PlanetScale
   - `DB_USER` = votre utilisateur
   - `DB_PASS` = votre password

### Option B : Utiliser une BD externe (AWS RDS, DigitalOcean, etc.)

Même processus : récupérez les identifiants et configurez-les dans Render.

### Importer le Schéma SQL

1. Connectez-vous à votre BD MySQL
2. Créez la base `foodexpress_dakar`
3. Importez le fichier `database/foodexpress_dakar.sql` :

```bash
mysql -h <host> -u <user> -p <password> foodexpress_dakar < database/foodexpress_dakar.sql
```

### 2.2 Vérifier la Connexion

Visitez votre app sur `https://votre-app.onrender.com` et vérifiez :
- ✅ La page d'accueil charge les plats depuis la BD
- ✅ L'admin panel fonctionne (`/admin/index.php`)
- ✅ Les comptes de démo sont accessibles

---

## 📁 Étape 3 : Gestion des Uploads

Les répertoires d'upload sont créés automatiquement :
- `assets/uploads/plats/` — Images des plats
- `assets/uploads/avatars/` — Avatars des utilisateurs

**Note :** Sur Render, les uploads sont éphémères (perdus à chaque redéploiement). Pour persister les uploads, utilisez :
- **Render Disks** (paiement requis)
- **Stockage externe** (AWS S3, Azure Blob, Cloudinary, etc.)

Exemple avec S3 (optionnel) :
```php
// Dans config/config.php
define('UPLOAD_DRIVER', getenv('UPLOAD_DRIVER') ?: 'local');
define('S3_KEY', getenv('S3_KEY'));
define('S3_SECRET', getenv('S3_SECRET'));
define('S3_BUCKET', getenv('S3_BUCKET'));
```

---

## 🔐 Comptes de Démo

| Email | Password | Rôle |
|-------|----------|------|
| admin@foodexpress.sn | admin123 | Admin |
| mouhamed@example.com | demo123 | Client |

**⚠️ Changez les mots de passe en production !**

---

## 🛠️ Troubleshooting

### Erreur : "SMTP connection failed"
- ✅ Vérifiez `SMTP_USER` et `SMTP_PASS` dans Render Dashboard
- ✅ Utilisez un **App Password Gmail**, pas votre mot de passe normal
- ✅ Vérifiez que les identifiants sont correctement configurés

### Erreur : "Database connection refused"
- ✅ Vérifiez que votre BD (PlanetScale, RDS, etc.) est accessible
- ✅ Confirmez que `DB_HOST`, `DB_USER`, `DB_PASS` sont corrects
- ✅ Vérifiez les logs : Render Dashboard → **Logs**

### Erreur Docker : "Build failed"
- ✅ Vérifiez que `Dockerfile` et `render.yaml` existent à la racine du repo
- ✅ Consultez les logs de build dans Render Dashboard
- ✅ Assurez-vous que tous les fichiers sont en UTF-8

### Images des plats ne s'affichent pas
- ✅ Les uploads sont éphémères sur Render (perdus après redéploiement)
- ✅ Utilisez des URLs externes (Google Images, CDN)
- ✅ Ou configurez un stockage S3

### La page blanche / Erreur 500
- ✅ Vérifiez les logs : Render Dashboard → **Logs**
- ✅ Activez le debug : `APP_DEBUG = true` (temporairement)
- ✅ Vérifiez les permissions des répertoires

---

## 📊 Coûts Estimés

| Service | Plan | Coût |
|---------|------|------|
| Web (Docker PHP) | Free | Gratuit |
| MySQL (PlanetScale) | Free | Gratuit |
| **Total** | | **Gratuit** 🎉 |

**Limitations du plan Free :**
- Web : reboot automatique après inactivité (15 min)
- Builds Docker limités
- Storage uploads éphémères

Pour la production, upgrader à **Standard** ($7/mois+).

---

## 🚀 Déploiement Continu

À chaque push sur GitHub (`main`) :
1. Render détecte automatiquement le changement
2. Reconstruit l'image Docker
3. Redéploie l'app avec zéro downtime

### Éviter les redéploiements accidentels

Créez une branche `dev` :
```bash
git checkout -b dev
git push origin dev
```

Configurez `render.yaml` pour surveiller `main` uniquement.

---

## 📁 Fichiers Importants

- **`render.yaml`** — Configuration Blueprint pour Render
- **`Dockerfile`** — Image Docker PHP 8.2
- **`.dockerignore`** — Fichiers à exclure du build
- **`config/config.php`** — Configuration application (variables d'environnement)
- **`database/foodexpress_dakar.sql`** — Schéma BD

---

## 📞 Support

- **Render Docs** : https://render.com/docs
- **GitHub Issues** : https://github.com/khalildino1-sudo/foodexpress-dakar/issues
- **Email** : contact@foodexpress.sn

---

**Bonne chance pour votre déploiement! 🍛**

