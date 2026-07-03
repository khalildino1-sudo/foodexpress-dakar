<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        $avisId = (int)($_POST['id'] ?? 0);

        if ($action === 'approuver') {
            $pdo->prepare('UPDATE avis SET approuve = 1 WHERE id = ?')->execute([$avisId]);
            // Recalcule la note moyenne du plat
            $stmt = $pdo->prepare('SELECT plat_id FROM avis WHERE id = ?');
            $stmt->execute([$avisId]);
            $platId = $stmt->fetchColumn();
            if ($platId) {
                $pdo->prepare("UPDATE plats SET note_moyenne = (SELECT ROUND(AVG(note),1) FROM avis WHERE plat_id = ? AND approuve = 1) WHERE id = ?")
                    ->execute([$platId, $platId]);
            }
            setFlash('success', 'Avis approuvé et publié.');
        }
        if ($action === 'rejeter') {
            $pdo->prepare('UPDATE avis SET approuve = 0 WHERE id = ?')->execute([$avisId]);
            setFlash('success', 'Avis masqué.');
        }
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM avis WHERE id = ?')->execute([$avisId]);
            setFlash('success', 'Avis supprimé.');
        }
    }
    redirect('avis.php');
}

$filter = $_GET['f'] ?? 'all';
$where = match($filter) {
    'pending' => 'WHERE a.approuve = 0',
    'approved' => 'WHERE a.approuve = 1',
    default => '',
};

$avis = $pdo->query("
    SELECT a.*, u.prenom, u.nom, p.nom AS plat_nom
    FROM avis a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN plats p ON p.id = a.plat_id
    $where
    ORDER BY a.created_at DESC
")->fetchAll();

$nbPending = $pdo->query("SELECT COUNT(*) FROM avis WHERE approuve = 0")->fetchColumn();

$pageTitle = 'Avis clients';
$activeMenu = 'avis';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Filtres -->
<div class="bg-white rounded-2xl p-2 shadow-soft border border-outline-variant/20 mb-6 flex gap-1">
    <a href="?f=all" class="px-4 py-2 rounded-xl text-sm font-medium <?= $filter === 'all' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">Tous</a>
    <a href="?f=pending" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium <?= $filter === 'pending' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">
        En attente
        <?php if ($nbPending > 0): ?><span class="text-xs px-1.5 py-0.5 rounded-full <?= $filter === 'pending' ? 'bg-white/20' : 'bg-error-container text-error' ?>"><?= $nbPending ?></span><?php endif; ?>
    </a>
    <a href="?f=approved" class="px-4 py-2 rounded-xl text-sm font-medium <?= $filter === 'approved' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">Publiés</a>
</div>

<!-- Liste des avis -->
<div class="space-y-4">
    <?php foreach ($avis as $a): ?>
        <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0">
                    <?= strtoupper(substr($a['prenom'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium"><?= e($a['prenom'] . ' ' . $a['nom']) ?></span>
                        <div class="flex">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="material-symbols-outlined text-base <?= $i <= $a['note'] ? 'text-secondary icon-fill' : 'text-outline-variant' ?>">star</span>
                            <?php endfor; ?>
                        </div>
                        <?php if ($a['approuve']): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-tertiary text-white">Publié</span>
                        <?php else: ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container">En attente</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($a['plat_nom']): ?>
                        <div class="text-xs text-on-surface-variant mt-0.5">Sur le plat : <strong><?= e($a['plat_nom']) ?></strong></div>
                    <?php endif; ?>
                    <p class="text-sm mt-2 text-on-surface"><?= e($a['commentaire']) ?></p>
                    <div class="text-xs text-on-surface-variant mt-2"><?= timeAgo($a['created_at']) ?></div>
                </div>
                <div class="flex flex-col gap-2">
                    <?php if (!$a['approuve']): ?>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="approuver">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="p-2 bg-tertiary text-white rounded-lg hover:brightness-110 transition-all" title="Approuver">
                                <span class="material-symbols-outlined text-base">check</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="rejeter">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="p-2 bg-secondary-container text-on-secondary-container rounded-lg hover:brightness-95 transition-all" title="Masquer">
                                <span class="material-symbols-outlined text-base">visibility_off</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Supprimer cet avis ?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button class="p-2 bg-error-container text-error rounded-lg hover:bg-error hover:text-white transition-colors" title="Supprimer">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$avis): ?>
        <div class="bg-white rounded-2xl p-12 text-center shadow-soft border border-outline-variant/20">
            <span class="material-symbols-outlined text-6xl text-on-surface-variant">reviews</span>
            <p class="text-on-surface-variant mt-2">Aucun avis dans cette catégorie.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
