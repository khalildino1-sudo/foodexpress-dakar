# 🚀 Déploiement FoodExpress Dakar sur Render

Guide complet pour héberger FoodExpress Dakar sur la plateforme **Render.com** avec Blueprint.

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

### 1.2 Configurer les Variables d'Environnement

Après le déploiement, allez dans **Environment** → **Environment Variables** et remplissez :

#### Base de Données
```
DB_HOST = <généré automatiquement>
DB_PORT = 3306
DB_NAME = foodexpress_dakar
DB_USER = foodexpress_user
DB_PASS = <généré automatiquement>
```

#### Email (Gmail SMTP)
```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USER = votre_email@gmail.com
SMTP_PASS = votre_app_password
SMTP_FROM_EMAIL = votre_email@gmail.com
SMTP_FROM_NAME = FoodExpress Dakar
```

**Comment générer un Gmail App Password :**
1. Allez sur https://myaccount.google.com/apppasswords
2. Sélectionnez **Mail** et **Windows Computer** (ou votre appareil)
3. Copiez le mot de passe généré
4. Collez-le dans `SMTP_PASS`

#### Application
```
APP_ENV = production
APP_DEBUG = false
APP_TIMEZONE = Africa/Dakar
APP_URL = https://votre-app.onrender.com
```

---

## 🗄️ Étape 2 : Configuration de la Base de Données

### 2.1 Importer le schéma SQL

1. Dans Render Dashboard, accédez au service **foodexpress-mysql**
2. Cliquez sur **Connect**
3. Utilisez l'onglet **MySQL CLI** ou un client MySQL
4. Exécutez les commandes :

```bash
# Connectez-vous avec les identifiants fournis
mysql -h <host> -u foodexpress_user -p <password> foodexpress_dakar

# Puis exécutez le SQL fourni (copier-coller depuis database/foodexpress_dakar.sql)
```

Ou utilisez **phpMyAdmin** (si disponible) :
1. Téléchargez le fichier `database/foodexpress_dakar.sql`
2. Importez-le dans Render MySQL via l'interface

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
- **Stockage externe** (AWS S3, Azure Blob, etc.)

Exemple avec S3 (optionnel) :
```php
// Dans config/config.php
define('UPLOAD_DRIVER', getenv('UPLOAD_DRIVER') ?: 'local');
define('S3_KEY', getenv('S3_KEY'));
define('S3_SECRET', getenv('S3_SECRET'));
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
- ✅ Vérifiez `SMTP_USER` et `SMTP_PASS`
- ✅ Activez les "Apps moins sécurisées" sur https://myaccount.google.com/lesssecureapps
- ✅ Utilisez un **App Password**, pas votre mot de passe Gmail

### Erreur : "Database connection refused"
- ✅ Vérifiez que le service MySQL est **running** (Render Dashboard)
- ✅ Confirmez que `DB_HOST`, `DB_USER`, `DB_PASS` sont corrects
- ✅ Attendez que la BD soit initialisée (peut prendre 1-2 min)

### Images des plats ne s'affichent pas
- ✅ Les uploads sont éphémères sur Render Free
- ✅ Utilisez des URLs externes (Google Images, CDN)
- ✅ Ou upgrader vers un **Render Disk**

### La page blanche / Erreur 500
- ✅ Vérifiez les logs : Render Dashboard → **Logs**
- ✅ Activez le debug : `APP_DEBUG = true` (temporairement)
- ✅ Vérifiez les permissions des répertoires

---

## 📊 Coûts Estimés

| Service | Plan | Coût |
|---------|------|------|
| Web (PHP) | Free | Gratuit |
| MySQL | Free | Gratuit |
| **Total** | | **Gratuit** 🎉 |

**Limitations du plan Free :**
- Web : reboot automatique après inactivité (15 min)
- MySQL : reboot quotidien, 1 GB stockage max
- Uploads éphémères

Pour la production, upgrader à **Standard** ($7/mois+).

---

## 🚀 Déploiement Continu

À chaque push sur GitHub (`main`) :
1. Render détecte automatiquement le changement
2. Reconstruit et redéploie l'app
3. Zéro downtime (déploiement bleu/vert)

Pour éviter les redéploiements accidentels, créez une branche `dev` :
```bash
git checkout -b dev
git push origin dev
```

Puis mettez à jour `render.yaml` pour surveiller `main` seulement.

---

## 📞 Support

- **Render Docs** : https://render.com/docs
- **GitHub Issues** : https://github.com/khalildino1-sudo/foodexpress-dakar/issues
- **Email** : contact@foodexpress.sn

---

**Bonne chance pour votre déploiement! 🍛**
