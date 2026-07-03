<?php
/**
 * Header client (navigation principale)
 */
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="sticky top-0 z-50 glass border-b border-outline-variant/30">
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-3 flex items-center justify-between">
        <!-- Logo -->
        <a href="<?= APP_URL ?>/client/index.php" class="flex items-center gap-2 group">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-md group-hover:rotate-6 transition-transform">
                <span class="material-symbols-outlined icon-fill">restaurant</span>
            </div>
            <div class="hidden sm:block">
                <div class="font-display font-bold text-lg leading-tight">FoodExpress</div>
                <div class="text-xs text-on-surface-variant -mt-0.5">Dakar · Sénégal</div>
            </div>
        </a>

        <!-- Nav desktop -->
        <nav class="hidden md:flex items-center gap-1">
            <a href="<?= APP_URL ?>/client/index.php" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $current === 'index' ? 'text-primary bg-primary-fixed' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">Accueil</a>
            <a href="<?= APP_URL ?>/client/menu.php" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $current === 'menu' ? 'text-primary bg-primary-fixed' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">Menu</a>
            <?php if (isLogged()): ?>
                <a href="<?= APP_URL ?>/client/commandes.php" class="px-4 py-2 rounded-lg font-medium transition-colors <?= in_array($current, ['commandes','suivi']) ? 'text-primary bg-primary-fixed' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">Mes commandes</a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/client/contact.php" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $current === 'contact' ? 'text-primary bg-primary-fixed' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">Contact</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <!-- Recherche desktop -->
            <form action="<?= APP_URL ?>/client/menu.php" method="get" class="hidden lg:flex items-center bg-surface-container-low px-3 py-2 rounded-xl border border-outline-variant/30 focus-within:border-primary transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant text-xl mr-1">search</span>
                <input type="text" name="q" placeholder="Un thieboudienne ?" class="bg-transparent border-none focus:ring-0 text-sm w-44 p-0">
            </form>

            <!-- Panier -->
            <a href="<?= APP_URL ?>/client/panier.php" class="relative p-2.5 rounded-xl hover:bg-surface-container-low transition-all active:scale-90">
                <span class="material-symbols-outlined text-on-surface-variant">shopping_cart</span>
                <?php $count = cartCount(); if ($count > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-primary text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-md"><?= $count ?></span>
                <?php endif; ?>
            </a>

            <!-- Compte -->
            <?php if (isLogged()): ?>
                <div class="relative group">
                    <button class="p-2.5 rounded-xl hover:bg-surface-container-low transition-all active:scale-90 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                            <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant text-base hidden sm:inline">expand_more</span>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-card border border-outline-variant/30 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all py-2 z-50">
                        <div class="px-4 py-2 border-b border-outline-variant/20">
                            <div class="font-semibold text-sm"><?= e($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
                            <div class="text-xs text-on-surface-variant truncate"><?= e($_SESSION['user_email'] ?? '') ?></div>
                        </div>
                        <a href="<?= APP_URL ?>/client/profil.php" class="flex items-center gap-3 px-4 py-2 hover:bg-surface-container-low text-sm">
                            <span class="material-symbols-outlined text-on-surface-variant text-xl">person</span> Mon profil
                        </a>
                        <a href="<?= APP_URL ?>/client/commandes.php" class="flex items-center gap-3 px-4 py-2 hover:bg-surface-container-low text-sm">
                            <span class="material-symbols-outlined text-on-surface-variant text-xl">receipt_long</span> Mes commandes
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="<?= APP_URL ?>/admin/index.php" class="flex items-center gap-3 px-4 py-2 hover:bg-surface-container-low text-sm">
                                <span class="material-symbols-outlined text-tertiary text-xl">admin_panel_settings</span> Admin
                            </a>
                        <?php endif; ?>
                        <div class="border-t border-outline-variant/20 mt-1 pt-1">
                            <a href="<?= APP_URL ?>/auth/logout.php" class="flex items-center gap-3 px-4 py-2 hover:bg-error-container text-sm text-error">
                                <span class="material-symbols-outlined text-xl">logout</span> Se déconnecter
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= APP_URL ?>/auth/login.php" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white font-medium text-sm hover:brightness-110 active:scale-95 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-base">login</span> Connexion
                </a>
                <a href="<?= APP_URL ?>/auth/login.php" class="sm:hidden p-2.5 rounded-xl hover:bg-surface-container-low transition-all">
                    <span class="material-symbols-outlined text-on-surface-variant">person</span>
                </a>
            <?php endif; ?>

            <!-- Burger mobile -->
            <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden p-2.5 rounded-xl hover:bg-surface-container-low transition-all">
                <span class="material-symbols-outlined text-on-surface-variant">menu</span>
            </button>
        </div>
    </div>

    <!-- Menu mobile -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-outline-variant/30 bg-white">
        <nav class="px-4 py-3 flex flex-col gap-1">
            <a href="<?= APP_URL ?>/client/index.php" class="px-4 py-3 rounded-lg <?= $current === 'index' ? 'bg-primary-fixed text-primary' : 'hover:bg-surface-container-low' ?>">Accueil</a>
            <a href="<?= APP_URL ?>/client/menu.php" class="px-4 py-3 rounded-lg <?= $current === 'menu' ? 'bg-primary-fixed text-primary' : 'hover:bg-surface-container-low' ?>">Menu</a>
            <?php if (isLogged()): ?>
                <a href="<?= APP_URL ?>/client/commandes.php" class="px-4 py-3 rounded-lg <?= $current === 'commandes' ? 'bg-primary-fixed text-primary' : 'hover:bg-surface-container-low' ?>">Mes commandes</a>
                <a href="<?= APP_URL ?>/client/profil.php" class="px-4 py-3 rounded-lg <?= $current === 'profil' ? 'bg-primary-fixed text-primary' : 'hover:bg-surface-container-low' ?>">Mon profil</a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/client/contact.php" class="px-4 py-3 rounded-lg <?= $current === 'contact' ? 'bg-primary-fixed text-primary' : 'hover:bg-surface-container-low' ?>">Contact</a>
        </nav>
    </div>
</header>

<!-- Messages flash -->
<?php if ($msg = getFlash('success')): ?>
<div class="max-w-7xl mx-auto px-4 md:px-8 mt-4">
    <div class="flex items-center gap-3 bg-tertiary text-white px-4 py-3 rounded-xl shadow-soft animate-slide-up">
        <span class="material-symbols-outlined">check_circle</span>
        <span class="flex-1"><?= e($msg) ?></span>
        <button onclick="this.parentElement.remove()" class="material-symbols-outlined hover:opacity-75">close</button>
    </div>
</div>
<?php endif; ?>
<?php if ($msg = getFlash('error')): ?>
<div class="max-w-7xl mx-auto px-4 md:px-8 mt-4">
    <div class="flex items-center gap-3 bg-error text-white px-4 py-3 rounded-xl shadow-soft animate-slide-up">
        <span class="material-symbols-outlined">error</span>
        <span class="flex-1"><?= e($msg) ?></span>
        <button onclick="this.parentElement.remove()" class="material-symbols-outlined hover:opacity-75">close</button>
    </div>
</div>
<?php endif; ?>
