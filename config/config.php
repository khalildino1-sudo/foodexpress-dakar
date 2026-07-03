<?php
/**
 * Configuration générale de l'application
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// Constantes de l'application
define('APP_NAME', 'FoodExpress Dakar');
define('APP_TAGLINE', 'Le goût authentique du Sénégal, livré chez vous');
define('APP_URL', 'http://localhost/foodexpress-dakar');
define('APP_EMAIL', 'contact@foodexpress.sn');
define('APP_TIMEZONE', 'Africa/Dakar');
define('APP_CURRENCY', 'FCFA');
define('APP_PHONE', '+221 33 800 00 00');

// Frais et limites
define('FRAIS_LIVRAISON', 1000);
define('COMMANDE_MIN', 2000);

// Config email (PHPMailer / Gmail SMTP)
// REMPLACE par tes vrais identifiants Gmail App Password
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ibrahima.ndiaye76@unchk.edu.sn');
define('SMTP_PASS', 'vmhhcgeamkpibxrf');
define('SMTP_FROM_NAME', 'FoodExpress Dakar');
define('SMTP_FROM_EMAIL', 'ibrahima.ndiaye76@unchk.edu.sn');

// === Google Maps ===
// Laisse vide pour utiliser la carte gratuite sans clé.
// Pour la version avancée (marqueurs, itinéraires), crée une clé sur
// https://console.cloud.google.com/ et colle-la ici.
define('GOOGLE_MAPS_API_KEY', '');

// Quartiers de Dakar desservis
define('QUARTIERS_DAKAR', [
    'Plateau', 'Almadies', 'Point E', 'Mermoz', 'Sacré-Cœur',
    'Fann', 'Liberté 6', 'Yoff', 'Ngor', 'Ouakam',
    'Médina', 'HLM', 'Parcelles Assainies', 'Grand-Yoff', 'Sicap'
]);

date_default_timezone_set(APP_TIMEZONE);

// Charger les helpers
require_once __DIR__ . '/../includes/functions.php';