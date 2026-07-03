<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$statutFilter = $_GET['statut'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT c.*, u.prenom, u.nom, u.telephone AS tel_user
        FROM commandes c JOIN users u ON u.id = c.user_id WHERE 1=1";
$params = [];
if ($statutFilter !== '' && in_array($statutFilter, ['en_attente','confirmee','en_preparation','en_livraison','livree','annulee'])) {
    $sql .= " AND c.statut = ?";
    $params[] = $statutFilter;
}
if ($search !== '') {
    $sql .= " AND (c.numero LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Compteurs par statut
$compteurs = [];
$stmt = $pdo->query("SELECT statut, COUNT(*) c FROM commandes GROUP BY statut");
foreach ($stmt as $r) { $compteurs[$r['statut']] = $r['c']; }
$totalCmd = array_sum($compteurs);

$onglets = [
    ''               => ['Toutes', $totalCmd],
    'en_attente'     => ['En attente', $compteurs['en_attente'] ?? 0],
    'confirmee'      => ['Confirmées', $compteurs['confirmee'] ?? 0],
    'en_preparation' => ['Préparation', $compteurs['en_preparation'] ?? 0],
    'en_livraison'   => ['Livraison', $compteurs['en_livraison'] ?? 0],
    'livree'         => ['Livrées', $compteurs['livree'] ?? 0],
    'annulee'        => ['Annulées', $compteurs['annulee'] ?? 0],
];

$pageTitle = 'Commandes';
$activeMenu = 'commandes';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Onglets de filtre -->
<div class="bg-white rounded-2xl p-2 shadow-soft border border-outline-variant/20 mb-4 flex gap-1 overflow-x-auto scrollbar-hide">
    <?php foreach ($onglets as $key => $info): ?>
        <a href="?statut=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           class="flex items-center gap-2 px-4 py-2 rounded-xl whitespace-nowrap text-sm font-medium transition-colors <?= $statutFilter === $key ? 'bg-primary text-white' : 'hover:bg-surface-container-low text-on-surface-variant' ?>">
            <?= $info[0] ?>
            <span class="text-xs px-1.5 py-0.5 rounded-full <?= $statutFilter === $key ? 'bg-white/20' : 'bg-surface-container' ?>"><?= $info[1] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Recherche -->
<form method="get" class="mb-6">
    <input type="hidden" name="statut" value="<?= e($statutFilter) ?>">
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant">search</span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par numéro ou client..."
               class="w-full pl-11 pr-4 py-2.5 bg-white border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 shadow-soft">
    </div>
</form>

<!-- Tableau -->
<div class="bg-white rounded-2xl shadow-soft border border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-low">
                <tr class="text-left text-on-surface-variant">
                    <th class="py-3 px-4 font-medium">Commande</th>
                    <th class="py-3 px-4 font-medium">Client</th>
                    <th class="py-3 px-4 font-medium">Quartier</th>
                    <th class="py-3 px-4 font-medium">Total</th>
                    <th class="py-3 px-4 font-medium">Paiement</th>
                    <th class="py-3 px-4 font-medium">Statut</th>
                    <th class="py-3 px-4 font-medium">Date</th>
                    <th class="py-3 px-4 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $c): ?>
                    <?php [$label, $cls] = statutLabel($c['statut']); ?>
                    <tr class="border-t border-outline-variant/15 hover:bg-surface-container-low transition-colors">
                        <td class="py-3 px-4 font-mono text-xs font-semibold"><?= e($c['numero']) ?></td>
                        <td class="py-3 px-4">
                            <div class="font-medium"><?= e($c['prenom'] . ' ' . $c['nom']) ?></div>
                            <div class="text-xs text-on-surface-variant"><?= e($c['telephone']) ?></div>
                        </td>
                        <td class="py-3 px-4"><?= e($c['quartier']) ?></td>
                        <td class="py-3 px-4 font-semibold text-primary"><?= formatPrice($c['total']) ?></td>
                        <td class="py-3 px-4"><span class="text-xs"><?= paiementLabel($c['methode_paiement']) ?></span></td>
                        <td class="py-3 px-4"><span class="text-xs px-2 py-1 rounded-full <?= $cls ?>"><?= $label ?></span></td>
                        <td class="py-3 px-4 text-xs text-on-surface-variant"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td class="py-3 px-4">
                            <a href="commande-detail.php?id=<?= $c['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-fixed text-primary rounded-lg text-xs font-medium hover:bg-primary-fixed-dim transition-colors">
                                <span class="material-symbols-outlined text-base">visibility</span> Détails
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$commandes): ?>
                    <tr><td colspan="8" class="py-12 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-5xl block mb-2">receipt_long</span>
                        Aucune commande trouvée.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
