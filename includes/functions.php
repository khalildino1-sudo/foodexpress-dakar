<?php
/**
 * Fonctions utilitaires de l'application
 */

/**
 * Échapper le HTML pour éviter XSS
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Rediriger vers une URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Formater un prix en FCFA
 */
function formatPrice($amount) {
    return number_format((float)$amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Générer un token CSRF
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 */
function csrfVerify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Champ caché CSRF (à insérer dans les formulaires)
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * L'utilisateur est-il connecté ?
 */
function isLogged() {
    return isset($_SESSION['user_id']);
}

/**
 * L'utilisateur est-il admin ?
 */
function isAdmin() {
    return isLogged() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Forcer l'authentification (sinon redirige)
 */
function requireLogin() {
    if (!isLogged()) {
        $_SESSION['flash_error'] = 'Vous devez être connecté pour accéder à cette page.';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('../auth/login.php');
    }
}

/**
 * Forcer l'accès admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
        redirect('../auth/login.php');
    }
}

/**
 * Définir un message flash
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Récupérer et effacer un message flash
 */
function getFlash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Slug à partir d'une chaîne
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'n-a';
}

/**
 * Générer un numéro de commande unique
 */
function generateOrderNumber($pdo) {
    do {
        $num = 'CMD-' . date('Y') . '-' . str_pad(random_int(1, 99999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM commandes WHERE numero = ?');
        $stmt->execute([$num]);
    } while ($stmt->fetchColumn() > 0);
    return $num;
}

/**
 * Nombre d'articles dans le panier
 */
function cartCount() {
    if (empty($_SESSION['cart'])) return 0;
    return array_sum(array_column($_SESSION['cart'], 'quantite'));
}

/**
 * Total du panier
 */
function cartTotal() {
    if (empty($_SESSION['cart'])) return 0;
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ($item['prix'] + ($item['supplement'] ?? 0)) * $item['quantite'];
    }
    return $total;
}

/**
 * Libellé d'un statut de commande
 */
function statutLabel($statut) {
    $labels = [
        'en_attente'      => ['En attente', 'bg-secondary-container text-on-secondary-container'],
        'confirmee'       => ['Confirmée', 'bg-tertiary-fixed text-on-tertiary-fixed'],
        'en_preparation'  => ['En préparation', 'bg-secondary-fixed text-on-secondary-fixed'],
        'en_livraison'    => ['En livraison', 'bg-primary-fixed text-on-primary-fixed'],
        'livree'          => ['Livrée', 'bg-tertiary text-on-tertiary'],
        'annulee'         => ['Annulée', 'bg-error-container text-on-error-container'],
    ];
    return $labels[$statut] ?? [$statut, 'bg-gray-200'];
}

/**
 * Méthode de paiement label
 */
function paiementLabel($methode) {
    return [
        'especes'       => 'Espèces à la livraison',
        'wave'          => 'Wave',
        'orange_money'  => 'Orange Money',
        'carte'         => 'Carte bancaire',
    ][$methode] ?? $methode;
}

/**
 * Temps écoulé en français
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'à l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return date('d/m/Y', $time);
}
