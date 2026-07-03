<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM promotions WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            setFlash('success', 'Promotion supprimée.');
        }

        if ($action === 'toggle') {
            $pdo->prepare('UPDATE promotions SET actif = 1 - actif WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            setFlash('success', 'Statut de la promotion modifié.');
        }

        if ($action === 'save') {
            $pid    = (int)($_POST['id'] ?? 0);
            $code   = strtoupper(trim($_POST['code'] ?? ''));
            $desc   = trim($_POST['description'] ?? '');
            $type   = $_POST['type'] ?? 'pourcentage';
            $valeur = (float)($_POST['valeur'] ?? 0);
            $min    = (float)($_POST['montant_min'] ?? 0);
            $debut  = $_POST['date_debut'] ?: null;
            $fin    = $_POST['date_fin'] ?: null;
            $maxUse = $_POST['utilisations_max'] !== '' ? (int)$_POST['utilisations_max'] : null;

            if ($code === '' || $valeur <= 0) {
                setFlash('error', 'Code et valeur sont obligatoires.');
            } else {
                try {
                    if ($pid > 0) {
                        $pdo->prepare("UPDATE promotions SET code=?, description=?, type=?, valeur=?, montant_min=?, date_debut=?, date_fin=?, utilisations_max=? WHERE id=?")
                            ->execute([$code, $desc, $type, $valeur, $min, $debut, $fin, $maxUse, $pid]);
                        setFlash('success', 'Promotion mise à jour.');
                    } else {
                        $pdo->prepare("INSERT INTO promotions (code, description, type, valeur, montant_min, date_debut, date_fin, utilisations_max) VALUES (?,?,?,?,?,?,?,?)")
                            ->execute([$code, $desc, $type, $valeur, $min, $debut, $fin, $maxUse]);
                        setFlash('success', 'Promotion créée.');
                    }
                } catch (PDOException $e) {
                    setFlash('error', 'Ce code promo existe déjà.');
                }
            }
        }
    }
    redirect('promotions.php');
}

$promotions = $pdo->query("SELECT * FROM promotions ORDER BY actif DESC, id DESC")->fetchAll();

$pageTitle = 'Promotions';
$activeMenu = 'promotions';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulaire -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20 sticky top-24">
            <h3 class="font-display font-semibold text-lg mb-4" id="formTitle">Nouvelle promotion</h3>
            <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="pId" value="">

                <div>
                    <label class="block text-sm font-medium mb-1.5">Code promo *</label>
                    <input type="text" name="code" id="pCode" required placeholder="DAKAR20" style="text-transform:uppercase"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Description</label>
                    <input type="text" name="description" id="pDesc"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Type</label>
                        <select name="type" id="pType" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                            <option value="pourcentage">Pourcentage</option>
                            <option value="montant_fixe">Montant fixe</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Valeur *</label>
                        <input type="number" name="valeur" id="pValeur" required step="0.01" min="0"
                               class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Montant minimum (FCFA)</label>
                    <input type="number" name="montant_min" id="pMin" value="0" min="0"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Début</label>
                        <input type="date" name="date_debut" id="pDebut"
                               class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Fin</label>
                        <input type="date" name="date_fin" id="pFin"
                               class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Utilisations max <span class="text-on-surface-variant font-normal">(vide = illimité)</span></label>
                    <input type="number" name="utilisations_max" id="pMax" min="1"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2.5 bg-primary text-white font-medium rounded-xl hover:brightness-110 active:scale-95 transition-all">Enregistrer</button>
                    <button type="button" onclick="resetForm()" class="px-4 py-2.5 bg-surface-container rounded-xl hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="lg:col-span-2 space-y-3">
        <?php foreach ($promotions as $p): ?>
            <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20 <?= !$p['actif'] ? 'opacity-60' : '' ?>">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">sell</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-primary"><?= e($p['code']) ?></span>
                                <span class="text-xs px-2 py-0.5 rounded-full <?= $p['actif'] ? 'bg-tertiary text-white' : 'bg-surface-container text-on-surface-variant' ?>">
                                    <?= $p['actif'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </div>
                            <p class="text-sm text-on-surface-variant"><?= e($p['description'] ?: 'Sans description') ?></p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick='editPromo(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                class="p-2 bg-primary-fixed text-primary rounded-lg hover:bg-primary-fixed-dim transition-colors">
                            <span class="material-symbols-outlined text-base">edit</span>
                        </button>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="p-2 bg-surface-container rounded-lg hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined text-base"><?= $p['actif'] ? 'toggle_on' : 'toggle_off' ?></span>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('Supprimer ce code promo ?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="p-2 bg-error-container text-error rounded-lg hover:bg-error hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 mt-3 pt-3 border-t border-outline-variant/15 text-xs text-on-surface-variant">
                    <span><strong class="text-on-surface"><?= $p['type'] === 'pourcentage' ? $p['valeur'] . '%' : formatPrice($p['valeur']) ?></strong> de réduction</span>
                    <span>Dès <strong class="text-on-surface"><?= formatPrice($p['montant_min']) ?></strong></span>
                    <span>Utilisé <strong class="text-on-surface"><?= $p['utilisations'] ?><?= $p['utilisations_max'] ? '/' . $p['utilisations_max'] : '' ?></strong></span>
                    <?php if ($p['date_fin']): ?>
                        <span>Expire le <strong class="text-on-surface"><?= date('d/m/Y', strtotime($p['date_fin'])) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$promotions): ?>
            <div class="bg-white rounded-2xl p-12 text-center shadow-soft border border-outline-variant/20">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant">sell</span>
                <p class="text-on-surface-variant mt-2">Aucune promotion. Créez-en une à gauche.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function editPromo(p) {
        document.getElementById('pId').value = p.id;
        document.getElementById('pCode').value = p.code;
        document.getElementById('pDesc').value = p.description || '';
        document.getElementById('pType').value = p.type;
        document.getElementById('pValeur').value = p.valeur;
        document.getElementById('pMin').value = p.montant_min;
        document.getElementById('pDebut').value = p.date_debut || '';
        document.getElementById('pFin').value = p.date_fin || '';
        document.getElementById('pMax').value = p.utilisations_max || '';
        document.getElementById('formTitle').textContent = 'Modifier la promotion';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function resetForm() {
        document.querySelector('form').reset();
        document.getElementById('pId').value = '';
        document.getElementById('formTitle').textContent = 'Nouvelle promotion';
    }
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
