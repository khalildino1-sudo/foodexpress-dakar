<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);

// === Mise à jour du statut ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'statut') {
            $newStatut = $_POST['statut'] ?? '';
            $valid = ['en_attente','confirmee','en_preparation','en_livraison','livree','annulee'];
            if (in_array($newStatut, $valid)) {
                $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?')->execute([$newStatut, $id]);
                // Synchroniser le paiement si livré
                if ($newStatut === 'livree') {
                    $pdo->prepare("UPDATE paiements SET statut = 'valide' WHERE commande_id = ?")->execute([$id]);
                    $pdo->prepare("UPDATE livraisons SET statut = 'livree', heure_livraison = NOW() WHERE commande_id = ?")->execute([$id]);
                }
                setFlash('success', 'Statut de la commande mis à jour.');
            }
        }
    }
    redirect('commande-detail.php?id=' . $id);
}

// === Charger la commande ===
$stmt = $pdo->prepare("
    SELECT c.*, u.prenom, u.nom, u.email, u.telephone AS tel_user
    FROM commandes c JOIN users u ON u.id = c.user_id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$commande = $stmt->fetch();

if (!$commande) {
    setFlash('error', 'Commande introuvable.');
    redirect('commandes.php');
}

$stmt = $pdo->prepare("SELECT * FROM details_commandes WHERE commande_id = ?");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM paiements WHERE commande_id = ? LIMIT 1");
$stmt->execute([$id]);
$paiement = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM livraisons WHERE commande_id = ? LIMIT 1");
$stmt->execute([$id]);
$livraison = $stmt->fetch();

[$label, $cls] = statutLabel($commande['statut']);

$pageTitle = 'Commande ' . $commande['numero'];
$activeMenu = 'commandes';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<div class="mb-4">
    <a href="commandes.php" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-base">arrow_back</span> Retour aux commandes
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Colonne principale -->
    <div class="lg:col-span-2 space-y-6">
        <!-- En-tête -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <div class="flex items-start justify-between flex-wrap gap-3">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-display font-bold text-2xl"><?= e($commande['numero']) ?></h2>
                        <span class="text-xs px-3 py-1 rounded-full <?= $cls ?>"><?= $label ?></span>
                    </div>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Passée le <?= date('d/m/Y à H:i', strtotime($commande['created_at'])) ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-on-surface-variant">Montant total</div>
                    <div class="font-display font-bold text-2xl text-primary"><?= formatPrice($commande['total']) ?></div>
                </div>
            </div>
        </div>

        <!-- Articles -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Articles commandés</h3>
            <div class="space-y-3">
                <?php foreach ($details as $d): ?>
                    <div class="flex items-center gap-3 pb-3 border-b border-outline-variant/15 last:border-0 last:pb-0">
                        <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary font-bold flex items-center justify-center flex-shrink-0">
                            <?= $d['quantite'] ?>×
                        </div>
                        <div class="flex-1">
                            <div class="font-medium"><?= e($d['nom_plat']) ?></div>
                            <?php if (!empty($d['options'])): ?>
                                <div class="text-xs text-tertiary">+ <?= e($d['options']) ?></div>
                            <?php endif; ?>
                            <div class="text-xs text-on-surface-variant"><?= formatPrice($d['prix_unitaire']) ?> l'unité</div>
                        </div>
                        <div class="font-semibold"><?= formatPrice($d['sous_total']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-on-surface-variant">Sous-total</span><span><?= formatPrice($commande['sous_total']) ?></span></div>
                <?php if ($commande['reduction'] > 0): ?>
                    <div class="flex justify-between text-tertiary"><span>Réduction <?= $commande['code_promo'] ? '(' . e($commande['code_promo']) . ')' : '' ?></span><span>- <?= formatPrice($commande['reduction']) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between"><span class="text-on-surface-variant">Frais de livraison</span><span><?= formatPrice($commande['frais_livraison']) ?></span></div>
                <div class="flex justify-between font-display font-bold text-lg pt-2 border-t border-outline-variant/20">
                    <span>Total</span><span class="text-primary"><?= formatPrice($commande['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <?php if (!empty($commande['instructions'])): ?>
            <div class="bg-secondary-fixed/30 rounded-2xl p-4 border border-secondary-fixed">
                <div class="flex items-center gap-2 text-sm font-medium text-on-secondary-fixed-variant mb-1">
                    <span class="material-symbols-outlined text-base">sticky_note_2</span> Instructions du client
                </div>
                <p class="text-sm text-on-secondary-fixed"><?= e($commande['instructions']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Colonne latérale -->
    <div class="space-y-6">
        <!-- Changement de statut -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Mettre à jour le statut</h3>
            <form method="post" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="statut">
                <select name="statut" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                    <?php
                    $statuts = ['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','en_livraison'=>'En livraison','livree'=>'Livrée','annulee'=>'Annulée'];
                    foreach ($statuts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $commande['statut'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="w-full py-2.5 bg-primary text-white font-medium rounded-xl hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">check</span> Appliquer
                </button>
            </form>
        </div>

        <!-- Client -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Client</h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-on-surface-variant text-base">person</span> <?= e($commande['prenom'] . ' ' . $commande['nom']) ?></div>
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-on-surface-variant text-base">mail</span> <?= e($commande['email']) ?></div>
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-on-surface-variant text-base">phone</span> <?= e($commande['telephone']) ?></div>
            </div>
        </div>

        <!-- Livraison -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Livraison</h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-start gap-2"><span class="material-symbols-outlined text-on-surface-variant text-base">location_on</span> <span><?= e($commande['adresse_livraison']) ?></span></div>
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-on-surface-variant text-base">map</span> <?= e($commande['quartier']) ?></div>
                <?php if ($livraison && $livraison['livreur']): ?>
                    <div class="mt-3 pt-3 border-t border-outline-variant/20">
                        <div class="text-xs text-on-surface-variant mb-1">Livreur assigné</div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-tertiary text-base">delivery_dining</span> <?= e($livraison['livreur']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <a href="livraisons.php" class="inline-flex items-center gap-1 text-sm text-primary hover:underline mt-3">
                Gérer la livraison <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        <!-- Paiement -->
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Paiement</h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-on-surface-variant">Méthode</span>
                    <span class="font-medium"><?= paiementLabel($commande['methode_paiement']) ?></span>
                </div>
                <?php if ($paiement): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface-variant">Statut</span>
                        <?php
                        $pStatut = ['en_attente'=>['En attente','text-secondary'],'valide'=>['Validé','text-tertiary'],'echoue'=>['Échoué','text-error'],'rembourse'=>['Remboursé','text-on-surface-variant']];
                        [$pl, $pc] = $pStatut[$paiement['statut']] ?? ['—','text-on-surface-variant'];
                        ?>
                        <span class="font-medium <?= $pc ?>"><?= $pl ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
