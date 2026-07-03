<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// === Statistiques globales ===
$stats = [];
$stats['commandes']   = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$stats['ca']          = $pdo->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut != 'annulee'")->fetchColumn();
$stats['clients']     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$stats['plats']       = $pdo->query("SELECT COUNT(*) FROM plats")->fetchColumn();
$stats['en_cours']    = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut IN ('en_attente','confirmee','en_preparation','en_livraison')")->fetchColumn();
$stats['avis_attente']= $pdo->query("SELECT COUNT(*) FROM avis WHERE approuve = 0")->fetchColumn();

// CA du jour
$caJour = $pdo->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE DATE(created_at) = CURDATE() AND statut != 'annulee'")->fetchColumn();
$cmdJour = $pdo->query("SELECT COUNT(*) FROM commandes WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// === Ventes des 7 derniers jours ===
$ventes7j = array_fill(0, 7, 0);
$labels7j = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels7j[] = date('D j', strtotime("-$i days"));
}
$stmt = $pdo->query("SELECT DATE(created_at) d, SUM(total) t FROM commandes WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND statut != 'annulee' GROUP BY DATE(created_at)");
$ventesByDate = [];
foreach ($stmt as $row) { $ventesByDate[$row['d']] = (float)$row['t']; }
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $ventes7j[6 - $i] = $ventesByDate[$date] ?? 0;
}

// === Répartition par statut ===
$statutData = [];
$stmt = $pdo->query("SELECT statut, COUNT(*) c FROM commandes GROUP BY statut");
foreach ($stmt as $row) { $statutData[$row['statut']] = (int)$row['c']; }

// === Top 5 plats ===
$topPlats = $pdo->query("
    SELECT p.nom, p.image, p.prix, SUM(dc.quantite) total_vendu, SUM(dc.sous_total) ca
    FROM details_commandes dc
    JOIN plats p ON p.id = dc.plat_id
    GROUP BY dc.plat_id
    ORDER BY total_vendu DESC
    LIMIT 5
")->fetchAll();

// === Dernières commandes ===
$dernieresCommandes = $pdo->query("
    SELECT c.*, u.prenom, u.nom
    FROM commandes c
    JOIN users u ON u.id = c.user_id
    ORDER BY c.created_at DESC
    LIMIT 6
")->fetchAll();

$pageTitle = 'Tableau de bord';
$activeMenu = 'dashboard';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Bandeau de bienvenue -->
<div class="bg-gradient-to-r from-primary to-primary-container rounded-2xl p-6 mb-6 text-white flex items-center justify-between flex-wrap gap-4">
    <div>
        <h2 class="font-display font-bold text-xl md:text-2xl">Bienvenue, <?= e($_SESSION['user_prenom'] ?? 'Admin') ?> 👋</h2>
        <p class="text-sm opacity-90">Voici les performances de FoodExpress à Dakar aujourd'hui.</p>
    </div>
    <div class="flex items-center gap-2 text-sm bg-white/15 px-4 py-2 rounded-xl">
        <span class="material-symbols-outlined text-base">calendar_today</span>
        <?= date('d/m/Y') ?>
    </div>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-primary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-primary icon-fill">payments</span>
            </div>
            <span class="text-xs font-medium text-tertiary bg-tertiary-fixed/40 px-2 py-1 rounded-full">Aujourd'hui</span>
        </div>
        <div class="font-display font-bold text-2xl"><?= formatPrice($stats['ca']) ?></div>
        <div class="text-sm text-on-surface-variant">Chiffre d'affaires total</div>
        <div class="text-xs text-tertiary mt-1 font-medium">+ <?= formatPrice($caJour) ?> aujourd'hui</div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-secondary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-secondary icon-fill">receipt_long</span>
            </div>
            <span class="text-xs font-medium text-secondary bg-secondary-fixed/40 px-2 py-1 rounded-full"><?= $cmdJour ?> auj.</span>
        </div>
        <div class="font-display font-bold text-2xl"><?= $stats['commandes'] ?></div>
        <div class="text-sm text-on-surface-variant">Commandes totales</div>
        <div class="text-xs text-secondary mt-1 font-medium"><?= $stats['en_cours'] ?> en cours</div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-tertiary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-tertiary icon-fill">group</span>
            </div>
        </div>
        <div class="font-display font-bold text-2xl"><?= $stats['clients'] ?></div>
        <div class="text-sm text-on-surface-variant">Clients inscrits</div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-primary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-primary icon-fill">lunch_dining</span>
            </div>
            <?php if ($stats['avis_attente'] > 0): ?>
                <span class="text-xs font-medium text-error bg-error-container px-2 py-1 rounded-full"><?= $stats['avis_attente'] ?> avis</span>
            <?php endif; ?>
        </div>
        <div class="font-display font-bold text-2xl"><?= $stats['plats'] ?></div>
        <div class="text-sm text-on-surface-variant">Plats au menu</div>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <!-- Ventes 7 jours -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-display font-semibold text-lg">Ventes des 7 derniers jours</h3>
                <p class="text-sm text-on-surface-variant">Évolution du chiffre d'affaires</p>
            </div>
            <span class="material-symbols-outlined text-primary">trending_up</span>
        </div>
        <canvas id="chartVentes" height="100"></canvas>
    </div>

    <!-- Répartition statuts -->
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
        <h3 class="font-display font-semibold text-lg mb-1">Statut des commandes</h3>
        <p class="text-sm text-on-surface-variant mb-4">Répartition globale</p>
        <canvas id="chartStatuts" height="180"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Top plats -->
    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
        <h3 class="font-display font-semibold text-lg mb-4">🔥 Top 5 des plats</h3>
        <div class="space-y-3">
            <?php foreach ($topPlats as $i => $p): ?>
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-primary-fixed text-primary font-bold text-sm flex items-center justify-center flex-shrink-0"><?= $i + 1 ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm truncate"><?= e($p['nom']) ?></div>
                        <div class="text-xs text-on-surface-variant"><?= $p['total_vendu'] ?> vendus</div>
                    </div>
                    <div class="text-sm font-semibold text-primary"><?= formatPrice($p['ca']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$topPlats): ?>
                <p class="text-sm text-on-surface-variant text-center py-4">Aucune vente pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dernières commandes -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-semibold text-lg">Dernières commandes</h3>
            <a href="commandes.php" class="text-sm text-primary hover:underline flex items-center gap-1">
                Tout voir <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
        <div class="overflow-x-auto -mx-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/30">
                        <th class="py-2 px-2 font-medium">N°</th>
                        <th class="py-2 px-2 font-medium">Client</th>
                        <th class="py-2 px-2 font-medium">Total</th>
                        <th class="py-2 px-2 font-medium">Statut</th>
                        <th class="py-2 px-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dernieresCommandes as $c): ?>
                        <?php [$label, $cls] = statutLabel($c['statut']); ?>
                        <tr class="border-b border-outline-variant/15 hover:bg-surface-container-low transition-colors">
                            <td class="py-3 px-2 font-mono text-xs"><?= e($c['numero']) ?></td>
                            <td class="py-3 px-2"><?= e($c['prenom'] . ' ' . $c['nom']) ?></td>
                            <td class="py-3 px-2 font-semibold"><?= formatPrice($c['total']) ?></td>
                            <td class="py-3 px-2"><span class="text-xs px-2 py-1 rounded-full <?= $cls ?>"><?= $label ?></span></td>
                            <td class="py-3 px-2">
                                <a href="commande-detail.php?id=<?= $c['id'] ?>" class="text-primary hover:underline">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dernieresCommandes): ?>
                        <tr><td colspan="5" class="py-6 text-center text-on-surface-variant">Aucune commande.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Graphique ventes
    new Chart(document.getElementById('chartVentes'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels7j) ?>,
            datasets: [{
                label: 'Ventes (FCFA)',
                data: <?= json_encode($ventes7j) ?>,
                borderColor: '#b7102a',
                backgroundColor: 'rgba(183,16,42,0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#b7102a',
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => (v/1000) + 'k' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Graphique statuts
    const statutLabels = {
        'en_attente': 'En attente', 'confirmee': 'Confirmée', 'en_preparation': 'En préparation',
        'en_livraison': 'En livraison', 'livree': 'Livrée', 'annulee': 'Annulée'
    };
    const statutRaw = <?= json_encode($statutData) ?>;
    new Chart(document.getElementById('chartStatuts'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statutRaw).map(k => statutLabels[k] || k),
            datasets: [{
                data: Object.values(statutRaw),
                backgroundColor: ['#ffab69', '#6fd8c8', '#ffb780', '#ffb3b1', '#00685d', '#ba1a1a'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
