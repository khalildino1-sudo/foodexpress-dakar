<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// === Suppression ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare('DELETE FROM plats WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Plat supprimé avec succès.');
        } catch (PDOException $e) {
            setFlash('error', 'Impossible de supprimer ce plat (commandes associées).');
        }
    }
    redirect('plats.php');
}

// === Activer / désactiver disponibilité ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE plats SET disponible = 1 - disponible WHERE id = ?')->execute([$id]);
        setFlash('success', 'Disponibilité mise à jour.');
    }
    redirect('plats.php');
}

// === Filtres ===
$search = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);

$sql = "SELECT p.*, c.nom AS categorie FROM plats p JOIN categories c ON c.id = p.categorie_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND p.nom LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($catFilter > 0) {
    $sql .= " AND p.categorie_id = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plats = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

$pageTitle = 'Gestion des plats';
$activeMenu = 'plats';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Barre d'actions -->
<div class="bg-white rounded-2xl p-4 shadow-soft border border-outline-variant/20 mb-6 flex flex-col md:flex-row gap-3 md:items-center">
    <form method="get" class="flex-1 flex gap-3">
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant">search</span>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher un plat..."
                   class="w-full pl-11 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
        </div>
        <select name="cat" onchange="this.form.submit()" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
            <option value="0">Toutes catégories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high rounded-xl transition-colors">
            <span class="material-symbols-outlined">filter_alt</span>
        </button>
    </form>
    <a href="plat-form.php" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-medium rounded-xl hover:brightness-110 active:scale-95 transition-all shadow-sm">
        <span class="material-symbols-outlined">add</span> Nouveau plat
    </a>
</div>

<!-- Tableau des plats -->
<div class="bg-white rounded-2xl shadow-soft border border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-low">
                <tr class="text-left text-on-surface-variant uppercase text-xs tracking-wider">
                    <th class="py-3 px-4 font-semibold">Visuel</th>
                    <th class="py-3 px-4 font-semibold">Nom du plat</th>
                    <th class="py-3 px-4 font-semibold">Catégorie</th>
                    <th class="py-3 px-4 font-semibold">Prix (FCFA)</th>
                    <th class="py-3 px-4 font-semibold">Status</th>
                    <th class="py-3 px-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plats as $p): ?>
                    <tr class="border-t border-outline-variant/15 hover:bg-surface-container-low transition-colors">
                        <!-- Visuel -->
                        <td class="py-3 px-4">
                            <div class="w-14 h-14 rounded-xl bg-surface-container overflow-hidden flex items-center justify-center">
                                <?php if ($p['image']): ?>
                                    <img src="../assets/uploads/<?= e($p['image']) ?>" onerror="this.src='https://placehold.co/100x100/edeeef/8f6f6e?text=Plat'" class="w-full h-full object-cover" alt="">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-on-surface-variant">restaurant</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <!-- Nom + référence -->
                        <td class="py-3 px-4">
                            <div class="font-semibold text-on-surface flex items-center gap-2">
                                <?= e($p['nom']) ?>
                                <?php if ($p['vedette']): ?>
                                    <span class="material-symbols-outlined text-secondary text-base icon-fill" title="Plat vedette">star</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-on-surface-variant">Réf : #FD-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </td>
                        <!-- Catégorie -->
                        <td class="py-3 px-4">
                            <span class="text-xs px-2.5 py-1 bg-surface-container rounded-full"><?= e($p['categorie']) ?></span>
                        </td>
                        <!-- Prix -->
                        <td class="py-3 px-4 font-semibold text-primary"><?= number_format($p['prix'], 0, ',', ' ') ?></td>
                        <!-- Status -->
                        <td class="py-3 px-4">
                            <?php if ($p['disponible']): ?>
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-tertiary"></span> En stock
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-error-container text-on-error-container font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Rupture
                                </span>
                            <?php endif; ?>
                        </td>
                        <!-- Actions -->
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="plat-form.php?id=<?= $p['id'] ?>" class="p-2 bg-primary-fixed text-primary rounded-lg hover:bg-primary-fixed-dim transition-colors" title="Modifier">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </a>
                                <form method="post" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="p-2 bg-surface-container hover:bg-surface-container-high rounded-lg transition-colors" title="Basculer la disponibilité">
                                        <span class="material-symbols-outlined text-base"><?= $p['disponible'] ? 'visibility' : 'visibility_off' ?></span>
                                    </button>
                                </form>
                                <form method="post" class="inline" onsubmit="return confirm('Supprimer ce plat ?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="p-2 bg-error-container text-error hover:bg-error hover:text-white rounded-lg transition-colors" title="Supprimer">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$plats): ?>
                    <tr><td colspan="6" class="py-12 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-5xl block mb-2">no_meals</span>
                        Aucun plat trouvé.
                        <div><a href="plat-form.php" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-primary text-white rounded-xl">
                            <span class="material-symbols-outlined">add</span> Ajouter le premier plat
                        </a></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
