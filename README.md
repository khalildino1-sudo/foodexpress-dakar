# 🍛 FoodExpress Dakar

Plateforme de commande et livraison de repas sénégalais — application web complète en **PHP 8 / MySQL**, avec espace client et espace d'administration.

---

## 📋 Prérequis

- **WAMP / XAMPP / MAMP** (ou tout serveur avec PHP 8.0+ et MySQL 5.7+)
- Un navigateur récent
- (Optionnel) **PHPMailer** pour l'envoi des emails

---

## 🚀 Installation (5 étapes)

### 1. Copier le projet
Placez le dossier `foodexpress-dakar` dans le répertoire web de WAMP :
```
C:\wamp64\www\foodexpress-dakar
```

### 2. Créer la base de données
Ouvrez **phpMyAdmin** (`http://localhost/phpmyadmin`) :
1. Créez une base nommée `foodexpress_dakar` (interclassement `utf8mb4_general_ci`)
2. Onglet **Importer** → sélectionnez le fichier `database/foodexpress_dakar.sql`
3. Cliquez sur **Exécuter**

> Le script crée les 9 tables et insère les données de démonstration (18 plats, catégories, commandes, avis, codes promo).

### 3. Vérifier la configuration
Ouvrez `config/database.php` et `config/config.php`.
Par défaut, la connexion utilise les identifiants WAMP standards :
```php
DB_HOST = localhost
DB_NAME = foodexpress_dakar
DB_USER = root
DB_PASS = ''   (vide)
```
Adaptez si votre MySQL utilise un autre mot de passe.

### 4. Générer les mots de passe
Ouvrez dans le navigateur :
```
http://localhost/foodexpress-dakar/install.php
```
Ce script régénère les **vrais hash bcrypt** des comptes de démonstration et crée les dossiers d'upload.

> ⚠️ **Supprimez `install.php` après cette étape** (sécurité).

### 5. Lancer l'application
```
http://localhost/foodexpress-dakar/client/index.php
```

---

## 🔑 Comptes de démonstration

| Rôle   | Email                     | Mot de passe |
|--------|---------------------------|--------------|
| Admin  | `admin@foodexpress.sn`    | `admin123`   |
| Client | `mouhamed@example.com`    | `demo123`    |
| Client | `aissatou@example.com`    | `demo123`    |
| Client | `ousmane@example.com`     | `demo123`    |

**Espace admin :** `http://localhost/foodexpress-dakar/admin/index.php`

---

## 📧 Configuration des emails (PHPMailer)

L'envoi d'emails (confirmation de commande, réinitialisation de mot de passe) est **optionnel** : l'application fonctionne sans, les emails sont simplement ignorés silencieusement.

Pour activer les emails :

1. Téléchargez PHPMailer : https://github.com/PHPMailer/PHPMailer/releases
2. Copiez le dossier `src/` dans :
   ```
   foodexpress-dakar/vendor/PHPMailer/src/
   ```
   Vous devez obtenir : `vendor/PHPMailer/src/PHPMailer.php`, `SMTP.php`, `Exception.php`
3. Dans `config/config.php`, renseignez vos identifiants SMTP Gmail :
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_USER', 'votre.email@gmail.com');
   define('SMTP_PASS', 'mot-de-passe-application'); // PAS votre mot de passe Gmail
   define('SMTP_PORT', 587);
   ```

> Pour Gmail, créez un **mot de passe d'application** dans les paramètres de sécurité de votre compte Google (l'authentification à deux facteurs doit être activée).

---

## 📂 Structure du projet

```
foodexpress-dakar/
├── config/           Connexion BDD et configuration globale
├── includes/         En-têtes, pieds de page, fonctions, mailer
├── auth/             Connexion, inscription, déconnexion, mot de passe oublié
├── client/           Espace client (menu, panier, commande, suivi, profil)
├── admin/            Espace admin (tableau de bord, plats, commandes, etc.)
├── assets/uploads/   Images téléversées (plats)
├── database/         Script SQL d'installation
├── vendor/           PHPMailer (à installer)
└── install.php       Script d'initialisation (à supprimer après usage)
```

---

## ⚙️ Fonctionnalités

### Espace client
- Catalogue de plats avec catégories et filtres
- Fiche détaillée de chaque plat (ingrédients, avis, plats similaires)
- Panier avec gestion des quantités et codes promo
- Tunnel de commande (adresse, quartier, mode de paiement)
- Suivi de commande en temps réel (timeline)
- Historique des commandes et profil modifiable
- Paiement : Espèces, Wave, Orange Money, Carte

### Espace admin
- Tableau de bord avec graphiques (ventes 7 jours, statuts)
- Gestion des plats (CRUD + upload d'images)
- Gestion des catégories et des codes promo
- Suivi et mise à jour des commandes
- Gestion des livraisons (assignation de livreurs)
- Gestion des utilisateurs et des rôles
- Modération des avis clients

---

## 🔒 Sécurité

- Mots de passe hashés avec **bcrypt**
- Protection **CSRF** sur tous les formulaires
- Requêtes **préparées PDO** (anti-injection SQL)
- Échappement HTML systématique (anti-XSS)
- Régénération de l'ID de session à la connexion
- Dossier `uploads/` protégé contre l'exécution de scripts

---

## 🛠️ Technologies

- **PHP 8** (PDO)
- **MySQL 8**
- **Tailwind CSS** (via CDN)
- **Chart.js** pour les graphiques
- **Material Symbols** pour les icônes
- **PHPMailer** pour les emails (optionnel)

---

*Développé pour la restauration sénégalaise — Teranga, qualité et passion. 🇸🇳*
