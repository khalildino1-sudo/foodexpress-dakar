<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$platId = (int)($_GET['plat_id'] ?? 0);

// Charger le plat
$stmt = $pdo->prepare('SELECT * FROM plats WHERE id = ?');
$stmt->execute([$platId]);
$plat = $stmt->fetch();
if (!$plat) {
    setFlash('error', 'Plat introuvable.');
    redirect('plats.php');
}

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $nom = trim($_POST['nom'] ?? '');
            $prix = (float)($_POST['prix_supplement'] ?? 0);
            if ($nom === '') {
                setFlash('error', 'Le nom de l\'option est obligatoire.');
            } else {
                $pdo->prepare('INSERT INTO options_plats (plat_id, nom, prix_supplement) VALUES (?, ?, ?)')
                    ->execute([$platId, $nom, $prix]);
                setFlash('success', 'Option ajoutée.');
            }
        }

        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM options_plats WHERE id = ? AND plat_id = ?')
                ->execute([(int)($_POST['id'] ?? 0), $platId]);
            setFlash('success', 'Option supprimée.');
        }

        if ($action === 'toggle') {
            $pdo->prepare('UPDATE options_plats SET actif = 1 - actif WHERE id = ? AND plat_id = ?')
                ->execute([(int)($_POST['id'] ?? 0), $platId]);
            setFlash('success', 'Disponibilité de l\'option modifiée.');
        }
    }
    redirect('plat-options.php?plat_id=' . $platId);
}

$options = $pdo->prepare('SELECT * FROM options_plats WHERE plat_id = ? ORDER BY id');
$options->execute([$platId]);
$options = $options->fetchAll();

$pageTitle = 'Options : ' . $plat['nom'];
$activeMenu = 'plats';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<div class="mb-4">
    <a href="plat-form.php?id=<?= $platId ?>" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-base">arrow_back</span> Retour au plat
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulaire d'ajout -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20 sticky top-24">
            <h3 class="font-display font-semibold text-lg mb-1">Nouvelle option</h3>
            <p class="text-sm text-on-surface-variant mb-4">Suppléments proposés pour « <?= e($plat['nom']) ?> »</p>
            <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nom de l'option *</label>
                    <input type="text" name="nom" required placeholder="Ex : Extra poisson"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Supplément (FCFA)</label>
                    <input type="number" name="prix_supplement" value="0" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <p class="text-xs text-on-surface-variant mt-1">Mettre 0 pour une option gratuite.</p>
                </div>
                <button type="submit" class="w-full py-2.5 bg-primary text-white font-medium rounded-xl hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">add</span> Ajouter l'option
                </button>
            </form>
        </div>
    </div>

    <!-- Liste des options -->
    <div class="lg:col-span-2 space-y-3">
        <?php foreach ($options as $opt): ?>
            <div class="bg-white rounded-2xl p-4 shadow-soft border border-outline-variant/20 flex items-center gap-4 <?= !$opt['actif'] ? 'opacity-60' : '' ?>">
                <div class="w-11 h-11 rounded-xl bg-primary-fixed flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-primary">add_circle</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium"><?= e($opt['nom']) ?></div>
                    <div class="text-sm <?= $opt['prix_supplement'] > 0 ? 'text-primary' : 'text-tertiary' ?>">
                        <?= $opt['prix_supplement'] > 0 ? '+ ' . formatPrice($opt['prix_supplement']) : 'Offert' ?>
                    </div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full <?= $opt['actif'] ? 'bg-tertiary text-white' : 'bg-surface-container text-on-surface-variant' ?>">
                    <?= $opt['actif'] ? 'Active' : 'Inactive' ?>
                </span>
                <div class="flex items-center gap-1.5">
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $opt['id'] ?>">
                        <button class="p-2 bg-surface-container rounded-lg hover:bg-surface-container-high transition-colors" title="Activer / désactiver">
                            <span class="material-symbols-outlined text-base"><?= $opt['actif'] ? 'toggle_on' : 'toggle_off' ?></span>
                        </button>
                    </form>
                    <form method="post" onsubmit="return confirm('Supprimer cette option ?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $opt['id'] ?>">
                        <button class="p-2 bg-error-container text-error rounded-lg hover:bg-error hover:text-white transition-colors" title="Supprimer">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$options): ?>
            <div class="bg-white rounded-2xl p-12 text-center shadow-soft border border-outline-variant/20">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant">tune</span>
                <p class="text-on-surface-variant mt-2">Aucune option pour ce plat. Ajoutez-en une à gauche.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
