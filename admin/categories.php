<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expirée.');
        redirect('categories.php');
    }

    if ($action === 'delete') {
        $cid = (int)($_POST['id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$cid]);
            setFlash('success', 'Catégorie supprimée.');
        } catch (PDOException $e) {
            setFlash('error', 'Impossible de supprimer : des plats y sont rattachés.');
        }
        redirect('categories.php');
    }

    if ($action === 'save') {
        $cid    = (int)($_POST['id'] ?? 0);
        $nom    = trim($_POST['nom'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $icone  = trim($_POST['icone'] ?? 'restaurant');
        $ordre  = (int)($_POST['ordre'] ?? 0);

        if ($nom === '') {
            setFlash('error', 'Le nom de la catégorie est obligatoire.');
        } else {
            $slug = slugify($nom);
            if ($cid > 0) {
                $pdo->prepare('UPDATE categories SET nom=?, slug=?, description=?, icone=?, ordre=? WHERE id=?')
                    ->execute([$nom, $slug, $desc, $icone, $ordre, $cid]);
                setFlash('success', 'Catégorie mise à jour.');
            } else {
                $pdo->prepare('INSERT INTO categories (nom, slug, description, icone, ordre) VALUES (?,?,?,?,?)')
                    ->execute([$nom, $slug, $desc, $icone, $ordre]);
                setFlash('success', 'Catégorie ajoutée.');
            }
        }
        redirect('categories.php');
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS nb_plats
    FROM categories c
    LEFT JOIN plats p ON p.categorie_id = c.id
    GROUP BY c.id
    ORDER BY c.ordre, c.nom
")->fetchAll();

$pageTitle = 'Catégories';
$activeMenu = 'categories';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulaire -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20 sticky top-24">
            <h3 class="font-display font-semibold text-lg mb-4" id="formTitle">Nouvelle catégorie</h3>
            <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="catId" value="">

                <div>
                    <label class="block text-sm font-medium mb-1.5">Nom *</label>
                    <input type="text" name="nom" id="catNom" required
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Description</label>
                    <textarea name="description" id="catDesc" rows="2"
                              class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Icône</label>
                        <input type="text" name="icone" id="catIcone" value="restaurant" placeholder="restaurant"
                               class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Ordre</label>
                        <input type="number" name="ordre" id="catOrdre" value="0"
                               class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    </div>
                </div>
                <p class="text-xs text-on-surface-variant">Icônes : noms Material Symbols (ex : restaurant, local_bar, cake...)</p>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2.5 bg-primary text-white font-medium rounded-xl hover:brightness-110 active:scale-95 transition-all">
                        Enregistrer
                    </button>
                    <button type="button" onclick="resetForm()" class="px-4 py-2.5 bg-surface-container rounded-xl hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="lg:col-span-2 space-y-3">
        <?php foreach ($categories as $c): ?>
            <div class="bg-white rounded-2xl p-4 shadow-soft border border-outline-variant/20 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-primary"><?= e($c['icone']) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="font-display font-semibold"><?= e($c['nom']) ?></h4>
                        <span class="text-xs px-2 py-0.5 bg-surface-container rounded-full"><?= $c['nb_plats'] ?> plat<?= $c['nb_plats'] > 1 ? 's' : '' ?></span>
                    </div>
                    <p class="text-sm text-on-surface-variant truncate"><?= e($c['description'] ?: 'Aucune description') ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick='editCat(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            class="p-2 bg-primary-fixed text-primary rounded-lg hover:bg-primary-fixed-dim transition-colors">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                    <form method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="p-2 bg-error-container text-error rounded-lg hover:bg-error hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$categories): ?>
            <div class="bg-white rounded-2xl p-12 text-center shadow-soft border border-outline-variant/20">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant">category</span>
                <p class="text-on-surface-variant mt-2">Aucune catégorie. Créez-en une à gauche.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function editCat(c) {
        document.getElementById('catId').value = c.id;
        document.getElementById('catNom').value = c.nom;
        document.getElementById('catDesc').value = c.description || '';
        document.getElementById('catIcone').value = c.icone || 'restaurant';
        document.getElementById('catOrdre').value = c.ordre || 0;
        document.getElementById('formTitle').textContent = 'Modifier la catégorie';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('catId').value = '';
        document.getElementById('catNom').value = '';
        document.getElementById('catDesc').value = '';
        document.getElementById('catIcone').value = 'restaurant';
        document.getElementById('catOrdre').value = 0;
        document.getElementById('formTitle').textContent = 'Nouvelle catégorie';
    }
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
