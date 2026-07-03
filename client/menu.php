<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$categorieSlug = $_GET['categorie'] ?? null;
$search        = trim($_GET['q'] ?? '');
$tri           = $_GET['tri'] ?? 'populaire';
$prixMax       = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? (int)$_GET['prix_max'] : null;

// Récupérer les catégories pour les filtres
$categories = $pdo->query('SELECT * FROM categories WHERE actif = 1 ORDER BY ordre')->fetchAll();

// Prix maximum disponible (pour le slider)
$prixPlafond = (int)($pdo->query('SELECT COALESCE(MAX(prix), 25000) FROM plats')->fetchColumn());
$prixPlafond = (int)(ceil($prixPlafond / 1000) * 1000);

// Catégorie active
$categorieActive = null;
if ($categorieSlug) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$categorieSlug]);
    $categorieActive = $stmt->fetch();
}

// Construction de la requête
$where = [];
$params = [];

if ($categorieActive) {
    $where[] = 'p.categorie_id = ?';
    $params[] = $categorieActive['id'];
}
if ($search !== '') {
    $where[] = '(p.nom LIKE ? OR p.description LIKE ? OR p.ingredients LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($prixMax !== null) {
    $where[] = 'p.prix <= ?';
    $params[] = $prixMax;
}

$orderBy = match($tri) {
    'prix_asc'  => 'p.prix ASC',
    'prix_desc' => 'p.prix DESC',
    'note'      => 'p.note_moyenne DESC',
    'recent'    => 'p.created_at DESC',
    default     => 'p.nb_ventes DESC',
};

$sql = 'SELECT p.*, c.nom AS categorie, c.slug AS categorie_slug
        FROM plats p
        JOIN categories c ON p.categorie_id = c.id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY p.disponible DESC, ' . $orderBy;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plats = $stmt->fetchAll();

$totalPlats = count($plats);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Notre carte - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <!-- Bandeau de titre -->
    <section class="bg-gradient-to-br from-primary via-primary-container to-primary text-white px-4 md:px-8 py-12 md:py-16 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-secondary-fixed/20 rounded-full blur-3xl"></div>
        <div class="max-w-7xl mx-auto relative z-10">
            <p class="text-sm font-semibold uppercase tracking-wider opacity-80 mb-2">La carte</p>
            <h1 class="font-display text-4xl md:text-5xl font-bold mb-2">
                <?= $categorieActive ? e($categorieActive['nom']) : 'Notre Menu' ?>
            </h1>
            <p class="opacity-90 max-w-2xl"><?= $categorieActive ? e($categorieActive['description']) : 'Découvrez l\'excellence culinaire de l\'Afrique de l\'Ouest.' ?></p>
        </div>
    </section>

    <!-- Barre de recherche et filtres -->
    <div class="sticky top-[68px] z-40 glass border-b border-outline-variant/30 px-4 md:px-8 py-4">
        <div class="max-w-7xl mx-auto">
            <form method="get" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center">
                <?php if ($categorieSlug): ?><input type="hidden" name="categorie" value="<?= e($categorieSlug) ?>"><?php endif; ?>

                <!-- Recherche -->
                <div class="flex-1 relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cherche un plat, ingrédient..." class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                <!-- Tri -->
                <select name="tri" onchange="this.form.submit()" class="px-4 py-3 rounded-xl border border-outline-variant bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all min-w-[180px]">
                    <option value="populaire" <?= $tri === 'populaire' ? 'selected' : '' ?>>Plus populaires</option>
                    <option value="note" <?= $tri === 'note' ? 'selected' : '' ?>>Mieux notés</option>
                    <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                    <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                    <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Nouveautés</option>
                </select>

                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:brightness-110 transition-all">Rechercher</button>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-8">
        <!-- Sidebar catégories -->
        <aside class="lg:sticky lg:top-[180px] lg:self-start">
            <h3 class="font-display font-semibold mb-4 text-lg">Catégories</h3>
            <nav class="flex lg:flex-col gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 lg:mx-0 lg:px-0 pb-2 lg:pb-0">
                <a href="menu.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="flex-shrink-0 px-3 py-2.5 rounded-xl flex items-center gap-3 transition-all <?= !$categorieSlug ? 'bg-primary text-white shadow-md' : 'bg-white border border-outline-variant/30 hover:border-primary' ?>">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 <?= !$categorieSlug ? 'bg-white/20' : 'bg-primary-fixed' ?>">
                        <span class="material-symbols-outlined icon-fill <?= !$categorieSlug ? 'text-white' : 'text-primary' ?>">grid_view</span>
                    </span>
                    <span class="font-medium text-sm whitespace-nowrap">Tout voir</span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <?php $catActive = $categorieSlug === $cat['slug']; ?>
                    <a href="menu.php?categorie=<?= e($cat['slug']) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="flex-shrink-0 px-3 py-2.5 rounded-xl flex items-center gap-3 transition-all <?= $catActive ? 'bg-primary text-white shadow-md' : 'bg-white border border-outline-variant/30 hover:border-primary' ?>">
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 <?= $catActive ? 'bg-white/20' : 'bg-primary-fixed' ?>">
                            <span class="material-symbols-outlined icon-fill <?= $catActive ? 'text-white' : 'text-primary' ?>"><?= e($cat['icone']) ?></span>
                        </span>
                        <span class="font-medium text-sm whitespace-nowrap"><?= e($cat['nom']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Filtre prix -->
            <div class="mt-6 bg-white border border-outline-variant/30 rounded-xl p-4">
                <h3 class="font-display font-semibold mb-3 text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">tune</span> Filtres
                </h3>
                <form method="get" id="filtreForm">
                    <?php if ($categorieSlug): ?><input type="hidden" name="categorie" value="<?= e($categorieSlug) ?>"><?php endif; ?>
                    <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
                    <input type="hidden" name="tri" value="<?= e($tri) ?>">
                    <label class="block text-sm font-medium mb-2">Prix maximum (FCFA)</label>
                    <input type="range" name="prix_max" min="0" max="<?= $prixPlafond ?>" step="500"
                           value="<?= $prixMax ?? $prixPlafond ?>"
                           oninput="document.getElementById('prixVal').textContent = Number(this.value).toLocaleString('fr-FR') + ' FCFA'"
                           onchange="document.getElementById('filtreForm').submit()"
                           class="w-full accent-primary cursor-pointer">
                    <div class="flex justify-between text-xs text-on-surface-variant mt-1">
                        <span>0</span>
                        <span id="prixVal" class="font-semibold text-primary"><?= number_format($prixMax ?? $prixPlafond, 0, ',', ' ') ?> FCFA</span>
                    </div>
                    <?php if ($prixMax !== null): ?>
                        <a href="menu.php?<?= http_build_query(array_filter(['categorie' => $categorieSlug, 'q' => $search, 'tri' => $tri])) ?>"
                           class="inline-flex items-center gap-1 text-xs text-primary hover:underline mt-3">
                            <span class="material-symbols-outlined text-sm">close</span> Réinitialiser le prix
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Grille des plats -->
        <main>
            <div class="flex items-center justify-between mb-6">
                <p class="text-on-surface-variant text-sm"><strong class="text-on-surface"><?= $totalPlats ?></strong> plat<?= $totalPlats > 1 ? 's' : '' ?> trouvé<?= $totalPlats > 1 ? 's' : '' ?><?= $search ? ' pour "' . e($search) . '"' : '' ?></p>
            </div>

            <?php if ($totalPlats === 0): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-outline-variant/30">
                    <div class="w-20 h-20 rounded-full bg-surface-container-high mx-auto mb-4 flex items-center justify-center">
                        <span class="material-symbols-outlined text-5xl text-on-surface-variant">search_off</span>
                    </div>
                    <h3 class="font-display font-semibold text-xl mb-2">Aucun plat trouvé</h3>
                    <p class="text-on-surface-variant mb-6">Essaie une autre recherche ou explore une autre catégorie.</p>
                    <a href="menu.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold">
                        <span class="material-symbols-outlined">refresh</span> Voir tous les plats
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <?php foreach ($plats as $plat): ?>
                        <article class="group bg-white rounded-2xl shadow-soft hover:shadow-hover transition-all border border-outline-variant/30 overflow-hidden hover:-translate-y-1 flex flex-col <?= !$plat['disponible'] ? 'opacity-70' : '' ?>">
                            <a href="plat.php?slug=<?= e($plat['slug']) ?>" class="aspect-[4/3] overflow-hidden bg-surface-container-low relative block">
                                <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($plat['image']) ?>"
                                     onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                                     class="w-full h-full object-cover <?= $plat['disponible'] ? 'group-hover:scale-110' : 'grayscale' ?> transition-transform duration-700" alt="<?= e($plat['nom']) ?>">
                                <?php if (!$plat['disponible']): ?>
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                        <span class="bg-error text-white text-xs font-bold px-3 py-1.5 rounded-full">Rupture de stock</span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-3 left-3 flex gap-2">
                                    <?php if ($plat['vedette']): ?>
                                        <span class="bg-secondary text-white text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm icon-fill">star</span> Vedette
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($plat['epice']): ?>
                                        <span class="bg-error text-white text-xs font-bold px-2.5 py-1 rounded-full">🌶️</span>
                                    <?php endif; ?>
                                </div>
                                <span class="absolute top-3 right-3 glass text-on-surface text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-secondary icon-fill">star</span> <?= number_format($plat['note_moyenne'], 1) ?>
                                </span>
                            </a>
                            <div class="p-5 flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-tertiary uppercase tracking-wider"><?= e($plat['categorie']) ?></span>
                                    <span class="text-xs text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">schedule</span> <?= $plat['temps_preparation'] ?>min
                                    </span>
                                </div>
                                <a href="plat.php?slug=<?= e($plat['slug']) ?>" class="block">
                                    <h3 class="font-display font-semibold text-lg mb-1.5 hover:text-primary transition-colors"><?= e($plat['nom']) ?></h3>
                                </a>
                                <p class="text-sm text-on-surface-variant mb-4 line-clamp-2 flex-1"><?= e($plat['description']) ?></p>
                                <div class="flex justify-between items-center mt-auto">
                                    <span class="font-display text-xl font-bold text-primary"><?= formatPrice($plat['prix']) ?></span>
                                    <?php if ($plat['disponible']): ?>
                                        <form method="post" action="panier.php">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="plat_id" value="<?= $plat['id'] ?>">
                                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl hover:scale-105 transition-transform shadow-md font-semibold text-sm flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-base">add_shopping_cart</span> Ajouter
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" disabled class="px-4 py-2 bg-surface-container text-on-surface-variant rounded-xl font-semibold text-sm flex items-center gap-1.5 cursor-not-allowed">
                                            <span class="material-symbols-outlined text-base">block</span> Indisponible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
