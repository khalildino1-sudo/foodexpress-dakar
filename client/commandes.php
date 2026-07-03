<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

requireLogin();

// Filtre par statut
$filtre = $_GET['filtre'] ?? 'toutes';
$stmt = $pdo->prepare('SELECT c.*, COUNT(d.id) AS nb_items FROM commandes c LEFT JOIN details_commandes d ON d.commande_id = c.id WHERE c.user_id = ? GROUP BY c.id ORDER BY c.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$toutesCommandes = $stmt->fetchAll();

// Application du filtre
$enCours = ['en_attente', 'confirmee', 'en_preparation', 'en_livraison'];
$commandes = match($filtre) {
    'livree'  => array_filter($toutesCommandes, fn($c) => $c['statut'] === 'livree'),
    'encours' => array_filter($toutesCommandes, fn($c) => in_array($c['statut'], $enCours)),
    'annulee' => array_filter($toutesCommandes, fn($c) => $c['statut'] === 'annulee'),
    default   => $toutesCommandes,
};

$totalDepense = array_sum(array_column($toutesCommandes, 'total'));
$nbCommandes = count($toutesCommandes);
$nbLivrees = count(array_filter($toutesCommandes, fn($c) => $c['statut'] === 'livree'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mes commandes - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <div class="mb-8">
            <h1 class="font-display text-3xl md:text-4xl font-bold mb-2">Historique des commandes</h1>
            <p class="text-on-surface-variant">Suivez vos repas favoris et recommandez en un clic</p>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-soft">
                <div class="flex items-center gap-2 text-on-surface-variant text-sm mb-1">
                    <span class="material-symbols-outlined text-base">receipt_long</span> Commandes
                </div>
                <div class="font-display text-2xl md:text-3xl font-bold"><?= $nbCommandes ?></div>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-soft">
                <div class="flex items-center gap-2 text-on-surface-variant text-sm mb-1">
                    <span class="material-symbols-outlined text-base">check_circle</span> Livrées
                </div>
                <div class="font-display text-2xl md:text-3xl font-bold text-tertiary"><?= $nbLivrees ?></div>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-soft">
                <div class="flex items-center gap-2 text-on-surface-variant text-sm mb-1">
                    <span class="material-symbols-outlined text-base">savings</span> Total dépensé
                </div>
                <div class="font-display text-2xl md:text-3xl font-bold text-primary"><?= formatPrice($totalDepense) ?></div>
            </div>
        </div>

        <!-- Filtres par statut -->
        <?php if (!empty($toutesCommandes)): ?>
            <div class="flex gap-2 mb-6 overflow-x-auto scrollbar-hide pb-1">
                <?php
                $ongletsHisto = [
                    'toutes'  => 'Toutes',
                    'livree'  => 'Livrées',
                    'encours' => 'En cours',
                    'annulee' => 'Annulées',
                ];
                foreach ($ongletsHisto as $key => $libelle):
                ?>
                    <a href="?filtre=<?= $key ?>"
                       class="flex-shrink-0 px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= $filtre === $key ? 'bg-primary text-white shadow-md' : 'bg-white border border-outline-variant/30 hover:border-primary' ?>">
                        <?= $libelle ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($commandes)): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-outline-variant/30 max-w-2xl mx-auto">
                <div class="w-24 h-24 rounded-full bg-primary-fixed mx-auto mb-6 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-primary">receipt_long</span>
                </div>
                <h2 class="font-display font-semibold text-2xl mb-2"><?= $filtre === 'toutes' ? 'Aucune commande pour le moment' : 'Aucune commande dans cette catégorie' ?></h2>
                <p class="text-on-surface-variant mb-8">Découvrez notre carte et passez votre première commande.</p>
                <a href="menu.php" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-3.5 rounded-xl font-semibold hover:brightness-110 transition-all shadow-md">
                    <span class="material-symbols-outlined">restaurant_menu</span> Voir la carte
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($commandes as $cmd): ?>
                    <?php [$label, $class] = statutLabel($cmd['statut']); ?>
                    <div class="block bg-white rounded-2xl border border-outline-variant/30 p-5 hover:shadow-card transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-primary-fixed flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-primary text-2xl">restaurant</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <h3 class="font-display font-semibold"><?= e($cmd['numero']) ?></h3>
                                        <span class="<?= $class ?> text-xs font-bold px-2.5 py-1 rounded-full"><?= $label ?></span>
                                    </div>
                                    <p class="text-sm text-on-surface-variant">
                                        <?= date('d M Y · H:i', strtotime($cmd['created_at'])) ?> ·
                                        <?= $cmd['nb_items'] ?> article<?= $cmd['nb_items'] > 1 ? 's' : '' ?> ·
                                        <?= e($cmd['quartier']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 md:gap-6 ml-auto md:ml-0">
                                <div class="text-right">
                                    <div class="font-display text-xl font-bold text-primary"><?= formatPrice($cmd['total']) ?></div>
                                    <div class="text-xs text-on-surface-variant"><?= e(paiementLabel($cmd['methode_paiement'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-outline-variant/20">
                            <a href="suivi.php?num=<?= e($cmd['numero']) ?>" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-surface-container hover:bg-surface-container-high rounded-lg text-sm font-medium transition-colors">
                                <span class="material-symbols-outlined text-base">visibility</span> Détails
                            </a>
                            <a href="menu.php" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary-fixed text-primary hover:bg-primary-fixed-dim rounded-lg text-sm font-medium transition-colors">
                                <span class="material-symbols-outlined text-base"><?= $cmd['statut'] === 'annulee' ? 'refresh' : 'replay' ?></span>
                                <?= $cmd['statut'] === 'annulee' ? 'Réessayer' : 'Re-commander' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
