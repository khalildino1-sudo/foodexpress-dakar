<?php
/**
 * En-tête de layout admin : ouvre le HTML, la sidebar et la zone de contenu.
 * Utilisation dans chaque page admin :
 *   $pageTitle = 'Tableau de bord';
 *   $activeMenu = 'dashboard';
 *   include '../includes/admin-sidebar.php';
 *   ... contenu ...
 *   include '../includes/admin-footer.php';
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
requireAdmin();

$pageTitle  = $pageTitle  ?? 'Administration';
$activeMenu = $activeMenu ?? '';

$menu = [
    ['key' => 'dashboard',    'url' => 'index.php',        'icon' => 'dashboard',       'label' => 'Tableau de bord'],
    ['key' => 'commandes',    'url' => 'commandes.php',    'icon' => 'receipt_long',    'label' => 'Commandes'],
    ['key' => 'plats',        'url' => 'plats.php',        'icon' => 'lunch_dining',    'label' => 'Plats'],
    ['key' => 'categories',   'url' => 'categories.php',   'icon' => 'category',        'label' => 'Catégories'],
    ['key' => 'livraisons',   'url' => 'livraisons.php',   'icon' => 'local_shipping',  'label' => 'Livraisons'],
    ['key' => 'utilisateurs', 'url' => 'utilisateurs.php', 'icon' => 'group',           'label' => 'Utilisateurs'],
    ['key' => 'avis',         'url' => 'avis.php',         'icon' => 'reviews',         'label' => 'Avis'],
    ['key' => 'promotions',   'url' => 'promotions.php',   'icon' => 'sell',            'label' => 'Promotions'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?= e($pageTitle) ?> · Admin FoodExpress</title>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="bg-surface-container-low">
<div class="flex min-h-screen">

    <!-- ===== SIDEBAR ===== -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-40 h-screen w-64 bg-inverse-surface text-inverse-on-surface flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
        <div class="p-6 border-b border-white/10">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
                    <span class="material-symbols-outlined icon-fill">restaurant</span>
                </div>
                <div>
                    <div class="font-display font-bold">FoodExpress</div>
                    <div class="text-xs opacity-60 -mt-0.5">Espace Admin</div>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 scrollbar-hide">
            <div class="text-xs font-semibold uppercase tracking-wider opacity-40 px-3 mb-2">Gestion</div>
            <?php foreach ($menu as $item): ?>
                <?php $isActive = $activeMenu === $item['key']; ?>
                <a href="<?= $item['url'] ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all <?= $isActive ? 'bg-primary text-white shadow-md' : 'hover:bg-white/10 opacity-80 hover:opacity-100' ?>">
                    <span class="material-symbols-outlined <?= $isActive ? 'icon-fill' : '' ?>"><?= $item['icon'] ?></span>
                    <span class="font-medium text-sm"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Pied sidebar -->
        <div class="p-3 border-t border-white/10">
            <a href="../client/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 opacity-80 hover:opacity-100 transition-all mb-1">
                <span class="material-symbols-outlined">storefront</span>
                <span class="font-medium text-sm">Voir le site</span>
            </a>
            <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-error/80 opacity-80 hover:opacity-100 transition-all">
                <span class="material-symbols-outlined">logout</span>
                <span class="font-medium text-sm">Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Overlay mobile -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    <!-- ===== ZONE PRINCIPALE ===== -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        <header class="sticky top-0 z-20 glass border-b border-outline-variant/30 px-4 md:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-surface-container">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div>
                    <h1 class="font-display font-bold text-xl md:text-2xl"><?= e($pageTitle) ?></h1>
                    <p class="text-xs text-on-surface-variant"><?= date('l j F Y') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="../client/index.php" target="_blank" class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high transition-colors text-sm">
                    <span class="material-symbols-outlined text-base">open_in_new</span> Site
                </a>
                <div class="flex items-center gap-2 pl-3 border-l border-outline-variant/40">
                    <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                        <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="hidden md:block">
                        <div class="text-sm font-semibold leading-tight"><?= e($_SESSION['user_prenom'] ?? 'Admin') ?></div>
                        <div class="text-xs text-on-surface-variant">Administrateur</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenu de page -->
        <main class="flex-1 p-4 md:p-8">
