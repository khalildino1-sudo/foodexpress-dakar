<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

requireLogin();

$numero = $_GET['num'] ?? '';
$stmt = $pdo->prepare('SELECT c.*, l.livreur, l.telephone_livreur, l.statut AS livraison_statut FROM commandes c LEFT JOIN livraisons l ON l.commande_id = c.id WHERE c.numero = ? AND c.user_id = ?');
$stmt->execute([$numero, $_SESSION['user_id']]);
$commande = $stmt->fetch();

if (!$commande) {
    setFlash('error', 'Commande introuvable.');
    redirect('commandes.php');
}

$stmt = $pdo->prepare('SELECT * FROM details_commandes WHERE commande_id = ?');
$stmt->execute([$commande['id']]);
$items = $stmt->fetchAll();

$statuts = ['en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree'];
$indexCurrent = array_search($commande['statut'], $statuts);
$progress = $commande['statut'] === 'annulee' ? 0 : (($indexCurrent !== false ? $indexCurrent : 0) + 1) * 20;

[$statutLabel, $statutClass] = statutLabel($commande['statut']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Suivi commande - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
    <meta http-equiv="refresh" content="60">
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <a href="commandes.php" class="text-primary text-sm font-semibold mb-4 inline-flex items-center gap-1 hover:underline">
            <span class="material-symbols-outlined text-base">arrow_back</span> Mes commandes
        </a>

        <!-- En-tête commande -->
        <div class="bg-gradient-to-br from-primary via-primary-container to-primary text-white rounded-2xl p-6 md:p-8 mb-6 shadow-card relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="text-sm opacity-80 uppercase tracking-wider font-semibold mb-1">Commande</div>
                    <h1 class="font-display text-3xl md:text-4xl font-bold"><?= e($commande['numero']) ?></h1>
                    <p class="opacity-90 mt-1">Passée le <?= date('d/m/Y à H:i', strtotime($commande['created_at'])) ?></p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-4 py-2 bg-white text-primary rounded-full font-semibold text-sm shadow-md"><?= $statutLabel ?></span>
                    <div class="font-display text-3xl font-bold mt-2"><?= formatPrice($commande['total']) ?></div>
                </div>
            </div>
        </div>

        <!-- Timeline progression -->
        <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 md:p-8 mb-6 shadow-soft">
            <h2 class="font-display font-semibold text-xl mb-6">Progression de la commande</h2>

            <?php if ($commande['statut'] === 'annulee'): ?>
                <div class="bg-error-container text-on-error-container rounded-xl p-4 flex items-center gap-3">
                    <span class="material-symbols-outlined">cancel</span>
                    <span>Cette commande a été annulée.</span>
                </div>
            <?php else: ?>
            <div class="relative">
                <!-- Ligne de fond -->
                <div class="absolute left-0 right-0 top-6 h-1 bg-surface-container-high rounded-full"></div>
                <!-- Ligne de progression -->
                <div class="absolute left-0 top-6 h-1 bg-primary rounded-full transition-all duration-1000" style="width: <?= max(0, $progress - 10) ?>%"></div>

                <div class="grid grid-cols-5 gap-2 relative">
                    <?php
                    $steps = [
                        ['en_attente', 'receipt', 'Reçue', 'On a bien reçu votre commande'],
                        ['confirmee', 'check_circle', 'Confirmée', 'Confirmée par le restaurant'],
                        ['en_preparation', 'cooking', 'Préparation', 'Nos chefs s\'y mettent'],
                        ['en_livraison', 'delivery_dining', 'Livraison', 'Le livreur est en route'],
                        ['livree', 'celebration', 'Livrée', 'Bon appétit !'],
                    ];
                    foreach ($steps as $i => $step):
                        $done = $i <= $indexCurrent;
                        $current = $i === $indexCurrent;
                    ?>
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center transition-all relative z-10 <?= $done ? 'bg-primary text-white shadow-md' : 'bg-surface-container-high text-on-surface-variant' ?> <?= $current ? 'ring-4 ring-primary-fixed animate-pulse-slow' : '' ?>">
                                <span class="material-symbols-outlined"><?= $step[1] ?></span>
                            </div>
                            <h4 class="font-semibold text-xs mt-2 <?= $done ? '' : 'text-on-surface-variant' ?>"><?= $step[2] ?></h4>
                            <p class="hidden md:block text-xs text-on-surface-variant mt-1"><?= $step[3] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Détails -->
            <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                <h2 class="font-display font-semibold text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">receipt_long</span> Détails
                </h2>
                <div class="space-y-3 mb-4 pb-4 border-b border-outline-variant/30">
                    <?php foreach ($items as $item): ?>
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex-1">
                                <div class="font-semibold text-sm"><?= e($item['nom_plat']) ?></div>
                                <div class="text-xs text-on-surface-variant"><?= formatPrice($item['prix_unitaire']) ?> × <?= $item['quantite'] ?></div>
                            </div>
                            <div class="font-semibold text-sm"><?= formatPrice($item['sous_total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Sous-total</span>
                        <span><?= formatPrice($commande['sous_total']) ?></span>
                    </div>
                    <?php if ($commande['reduction'] > 0): ?>
                    <div class="flex justify-between text-tertiary">
                        <span>Réduction</span>
                        <span>-<?= formatPrice($commande['reduction']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Livraison</span>
                        <span><?= formatPrice($commande['frais_livraison']) ?></span>
                    </div>
                    <div class="flex justify-between font-bold text-lg pt-2 border-t border-outline-variant/30 mt-2">
                        <span>Total</span>
                        <span class="text-primary"><?= formatPrice($commande['total']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Livraison & Paiement -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <h2 class="font-display font-semibold text-lg mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">location_on</span> Livraison
                    </h2>
                    <p class="text-sm mb-1"><?= e($commande['adresse_livraison']) ?></p>
                    <p class="text-sm text-on-surface-variant"><?= e($commande['quartier']) ?> · <?= e($commande['telephone']) ?></p>
                    <?php if (!empty($commande['instructions'])): ?>
                        <p class="text-sm italic mt-2 text-on-surface-variant border-l-4 border-primary pl-3">« <?= e($commande['instructions']) ?> »</p>
                    <?php endif; ?>
                    <?php if (!empty($commande['livreur'])): ?>
                        <div class="mt-4 pt-4 border-t border-outline-variant/30 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-tertiary text-white flex items-center justify-center">
                                <span class="material-symbols-outlined">delivery_dining</span>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">Livreur : <?= e($commande['livreur']) ?></div>
                                <a href="tel:<?= e($commande['telephone_livreur']) ?>" class="text-sm text-primary hover:underline"><?= e($commande['telephone_livreur']) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <h2 class="font-display font-semibold text-lg mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">payments</span> Paiement
                    </h2>
                    <p class="text-sm"><?= e(paiementLabel($commande['methode_paiement'])) ?></p>
                </div>

                <!-- Récapitulatif de la commande -->
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <h2 class="font-display font-semibold text-lg mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">receipt_long</span> Récapitulatif
                    </h2>
                    <div class="space-y-3">
                        <?php foreach ($items as $it): ?>
                            <div class="flex items-center justify-between text-sm">
                                <span><span class="font-semibold text-primary"><?= (int)$it['quantite'] ?>×</span> <?= e($it['nom_plat']) ?></span>
                                <span class="font-medium"><?= formatPrice($it['sous_total']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-1.5 text-sm">
                        <div class="flex justify-between text-on-surface-variant">
                            <span>Sous-total</span><span><?= formatPrice($commande['sous_total']) ?></span>
                        </div>
                        <?php if ($commande['reduction'] > 0): ?>
                            <div class="flex justify-between text-tertiary">
                                <span>Réduction</span><span>- <?= formatPrice($commande['reduction']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-on-surface-variant">
                            <span>Frais de livraison</span><span><?= formatPrice($commande['frais_livraison']) ?></span>
                        </div>
                        <div class="flex justify-between font-display font-bold text-base pt-2 border-t border-outline-variant/20">
                            <span>Total</span><span class="text-primary"><?= formatPrice($commande['total']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Encart aide -->
                <div class="bg-secondary-fixed/30 rounded-2xl border border-secondary-fixed p-6">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-secondary">support_agent</span>
                        <div>
                            <h3 class="font-semibold text-sm text-on-secondary-fixed-variant mb-1">Un problème avec votre commande ?</h3>
                            <p class="text-xs text-on-secondary-fixed mb-3">Notre équipe est disponible tous les jours de 8h à 23h pour vous aider.</p>
                            <a href="contact.php" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                                <span class="material-symbols-outlined text-base">chat</span> Contacter le support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-on-surface-variant">
            🔄 Cette page se met à jour automatiquement toutes les minutes.
        </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
