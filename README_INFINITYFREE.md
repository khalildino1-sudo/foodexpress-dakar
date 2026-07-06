# 📦 Guide de Déploiement - InfinityFree

**FoodExpress Dakar** - Déploiement sur InfinityFree avec MySQL et PHP

---

## 📋 Informations d'accès

Vos identifiants InfinityFree :

```
Host MySQL:        sql302.infinityfree.com
Port:              3306
Utilisateur:       if0_42345166
Mot de passe:      Khaliloulah10
Base de données:   if0_42345166_foodexpress
```

---

## 🔧 Étape 1 : Créer la base de données

### Via le panneau de contrôle InfinityFree

1. **Connectez-vous** à votre compte InfinityFree
2. **Allez à** : Bases de données > Gestionnaire MySQL
3. **Créez une nouvelle base** :
   - Nom : `if0_42345166_foodexpress`
   - Charset : `utf8mb4_unicode_ci`
4. **Confirmez** la création

### ✅ Vérification

Vous devriez voir la base listée dans votre gestionnaire MySQL.

---

## 💾 Étape 2 : Importer le schéma SQL

### Depuis phpMyAdmin

1. **Allez dans phpMyAdmin** depuis votre panneau InfinityFree
2. **Sélectionnez** la base `if0_42345166_foodexpress`
3. **Allez à l'onglet** : **Importer**
4. **Choisissez le fichier** : `database/foodexpress_dakar.sql` de ce projet
5. **Cliquez** : **Exécuter** ✅

### ⚠️ Important

Ne modifiez PAS le début du fichier SQL. Les instructions commentées expliquent le processus.

**Contenus créés après import :**
- 10 tables (users, categories, plats, commandes, etc.)
- 4 utilisateurs démo
- 18 plats sénégalais
- 4 commandes d'exemple
- Promotions et codes de réduction

---

## 📤 Étape 3 : Télécharger le projet sur InfinityFree

### Via FTP (FileZilla recommandé)

1. **Téléchargez FileZilla** : https://filezilla-project.org/
2. **Récupérez vos données FTP** depuis le panneau InfinityFree
3. **Connectez-vous par FTP** à votre compte
4. **Naviguez vers** : `public_html` ou `htdocs` (selon votre config)
5. **Téléchargez l'intégralité du projet** SAUF :
   - `.git` (historique Git)
   - `.gitignore`
   - `README*.md` (documents)
   - `vendor/PHPMailer/src/_PLACEZ_PHPMAILER_ICI.txt`

### 📁 Structure finale sur le serveur

```
public_html/
├── admin/
├── auth/
├── client/
├── config/
├── database/
├── includes/
├── assets/
├── vendor/
├── .env              ← À créer avec vos données
├── .env.example
├── index.php
├── install.php
└── README.md
```

---

## 🔐 Étape 4 : Configurer le fichier .env

### Créer le fichier .env

1. **Téléchargez le fichier** `.env.example` depuis le projet
2. **Renommez-le en** `.env`
3. **Modifiez les valeurs** :

```bash
# Laissez ces valeurs pour InfinityFree
DB_HOST=sql302.infinityfree.com
DB_PORT=3306
DB_NAME=if0_42345166_foodexpress
DB_USER=if0_42345166
DB_PASS=Khaliloulah10

# Gmail SMTP (pour les notifications par email)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=votre_email@gmail.com
SMTP_PASS=votre_mot_de_passe_application
SMTP_FROM_EMAIL=noreply@foodexpress-dakar.com

# Configuration app
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Africa/Dakar
APP_URL=https://votre-domaine.com
```

4. **Téléchargez le `.env` modifié** par FTP vers la racine du projet

---

## 📧 Étape 5 : Configurer Gmail SMTP (optionnel)

Si vous voulez que l'application envoie des emails de confirmation :

1. **Activez l'authentification 2FA** sur votre compte Gmail
2. **Générez un mot de passe d'application** :
   - Allez à : https://myaccount.google.com/apppasswords
   - Sélectionnez : Mail + Windows Computer
   - Copiez le mot de passe généré
3. **Insérez dans `.env`** :
   ```
   SMTP_USER=votre_email@gmail.com
   SMTP_PASS=[mot de passe généré]
   ```

---

## 🧪 Étape 6 : Tester l'application

### Accéder au site

1. **Ouvrez** : `https://votre-domaine.com` (ou IP temporaire InfinityFree)
2. **Vérifiez** que la page d'accueil charge

### Tester la page admin

1. **Allez à** : `https://votre-domaine.com/admin/`
2. **Identifiants de démo** :
   - Email : `admin@foodexpress.sn`
   - Mot de passe : `admin123`
3. ✅ Si vous entrez, la base de données est bien connectée

### Tester les plats

1. **Allez à** : `https://votre-domaine.com/client/menu.php`
2. **Vous devriez voir** : 18 plats sénégalais avec catégories

---

## ⚠️ Problèmes courants

### ❌ Erreur 403 Interdit

**Symptôme** : Vous accédez au site et voyez "403 Interdit"

**Solutions** :

#### 1. Vérifiez que `index.php` est présent
```
Fichiers requis à la racine:
✅ index.php      ← Doit être présent
✅ .htaccess      ← Configuration Apache
```

#### 2. Vérifiez les permissions des dossiers (via FTP)
```
public_html/          → 755
public_html/admin/    → 755
public_html/client/   → 755
public_html/assets/   → 755
```

#### 3. Si les permissions semblent correctes
- Attendez 5-10 minutes après l'upload
- Videz le cache du navigateur (Ctrl+Shift+Delete)
- Accédez directement à `/client/index.php` au lieu de `/`
- Vérifiez les logs d'erreur via le panneau d'admin InfinityFree

#### 4. Vérifiez que `.htaccess` est activé
- Dans le panneau InfinityFree, vérifiez que `mod_rewrite` est activé
- Si vous voyez toujours l'erreur 403, le `.htaccess` peut être mal interprété
- Essayez d'accéder directement à `/client/` sans `.htaccess`

### ❌ Les images ne s'affichent pas

**Solution** :
- Créez les dossiers manuellement par FTP :
  ```
  assets/uploads/
  assets/uploads/plats/
  assets/uploads/avatars/
  ```
- Donnez les permissions : `755` (lecture/exécution pour tous)

### ❌ Les emails ne s'envoient pas

**Solution** :
- Vérifiez que le mot de passe d'application Gmail est correct
- Assurez-vous que l'authentification 2FA est activée
- Vérifiez dans les logs de Gmail les tentatives échouées

### ❌ "Fatal error: Uncaught PDOException"

**Solution** :
- Le `.env` n'est pas au bon endroit
- Vérifiez que les credentials MySQL sont exacts
- Testez via le gestionnaire MySQL du panneau d'admin

### ❌ Erreur : "Access denied for user 'if0_42345166'"

**Solution** :
- Vérifiez que la base `if0_42345166_foodexpress` existe
- Vérifiez que le fichier `.env` est au bon endroit et bien formaté
- Redémarrez le service (attendre 5 min)

---

## 🔒 Sécurité

### À faire avant la mise en production

1. **Changez les mots de passe de démo** :
   ```php
   // admin/utilisateurs.php
   // Modifiez le hash du compte admin@foodexpress.sn
   ```

2. **Désactivez le mode debug** :
   ```bash
   APP_DEBUG=false
   ```

3. **Limitez les permissions** des dossiers sensibles :
   ```
   config/        → 700
   database/      → 700
   includes/      → 755
   ```

4. **Sauvegardez régulièrement** votre base de données

---

## 📞 Support

**Si vous rencontrez des problèmes :**

1. Consultez les logs d'erreur PHP du panneau InfinityFree
2. Vérifiez les permissions de dossier (via FTP)
3. Testez la connexion MySQL via phpMyAdmin
4. Vérifiez que les fichiers sont bien téléchargés par FTP

---

**Dernière mise à jour** : 2026-07-06  
**Version** : 1.0  
**Hébergeur** : InfinityFree
