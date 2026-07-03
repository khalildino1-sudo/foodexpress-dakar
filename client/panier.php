<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de sécurité invalide.');
        redirect('panier.php');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $platId = (int)($_POST['plat_id'] ?? 0);
        $quantite = max(1, min(20, (int)($_POST['quantite'] ?? 1)));
        $stmt = $pdo->prepare('SELECT id, nom, prix, image, slug FROM plats WHERE id = ? AND disponible = 1');
        $stmt->execute([$platId]);
        $plat = $stmt->fetch();
        if ($plat) {
            // Options sélectionnées
            $optionsChoisies = [];
            $supplementTotal = 0;
            $optionIds = array_map('intval', (array)($_POST['options'] ?? []));
            if ($optionIds) {
                $place = implode(',', array_fill(0, count($optionIds), '?'));
                $stmtOpt = $pdo->prepare("SELECT id, nom, prix_supplement FROM options_plats WHERE plat_id = ? AND actif = 1 AND id IN ($place)");
                $stmtOpt->execute(array_merge([$platId], $optionIds));
                foreach ($stmtOpt as $opt) {
                    $optionsChoisies[] = ['id' => $opt['id'], 'nom' => $opt['nom'], 'prix' => (float)$opt['prix_supplement']];
                    $supplementTotal += (float)$opt['prix_supplement'];
                }
            }
            // Clé unique : plat + combinaison d'options
            sort($optionIds);
            $cartKey = $platId . ($optionIds ? '_' . implode('-', $optionIds) : '');

            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantite'] = min(20, $_SESSION['cart'][$cartKey]['quantite'] + $quantite);
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'plat_id'    => $plat['id'],
                    'nom'        => $plat['nom'],
                    'slug'       => $plat['slug'],
                    'prix'       => (float)$plat['prix'],
                    'supplement' => $supplementTotal,
                    'options'    => $optionsChoisies,
                    'image'      => $plat['image'],
                    'quantite'   => $quantite,
                ];
            }
            setFlash('success', $plat['nom'] . ' ajouté au panier !');
        }
        redirect('panier.php');
    }

    if ($action === 'update') {
        $cartKey = (string)($_POST['cart_key'] ?? '');
        $quantite = max(0, min(20, (int)($_POST['quantite'] ?? 0)));
        if ($quantite === 0) {
            unset($_SESSION['cart'][$cartKey]);
            setFlash('success', 'Article retiré du panier.');
        } elseif (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantite'] = $quantite;
            setFlash('success', 'Panier mis à jour.');
        }
        redirect('panier.php');
    }

    if ($action === 'remove') {
        $cartKey = (string)($_POST['cart_key'] ?? '');
        unset($_SESSION['cart'][$cartKey]);
        setFlash('success', 'Article retiré du panier.');
        redirect('panier.php');
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        setFlash('success', 'Panier vidé.');
        redirect('panier.php');
    }

    if ($action === 'promo') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $stmt = $pdo->prepare('SELECT * FROM promotions WHERE code = ? AND actif = 1 AND (date_debut IS NULL OR date_debut <= CURDATE()) AND (date_fin IS NULL OR date_fin >= CURDATE())');
        $stmt->execute([$code]);
        $promo = $stmt->fetch();
        if ($promo && cartTotal() >= $promo['montant_min']) {
            $_SESSION['promo'] = $promo;
            setFlash('success', 'Code promo "' . $code . '" appliqué ! 🎉');
        } else {
            setFlash('error', 'Code promo invalide ou montant minimum non atteint.');
        }
        redirect('panier.php');
    }
}

$total = cartTotal();
$promo = $_SESSION['promo'] ?? null;
$reduction = 0;
if ($promo && $total >= $promo['montant_min']) {
    $reduction = $promo['type'] === 'pourcentage' ? ($total * $promo['valeur'] / 100) : $promo['valeur'];
}
$fraisLivraison = $total > 0 ? FRAIS_LIVRAISON : 0;
$grandTotal = max(0, $total - $reduction + $fraisLivraison);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mon panier - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <div class="mb-8">
            <h1 class="font-display text-3xl md:text-4xl font-bold mb-2">Mon panier</h1>
            <p class="text-on-surface-variant"><?= cartCount() ?> article<?= cartCount() > 1 ? 's' : '' ?> dans votre panier</p>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-outline-variant/30 max-w-2xl mx-auto">
                <div class="w-24 h-24 rounded-full bg-primary-fixed mx-auto mb-6 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-primary">shopping_cart</span>
                </div>
                <h2 class="font-display font-semibold text-2xl mb-2">Votre panier est vide</h2>
                <p class="text-on-surface-variant mb-8">Explore notre carte et compose un repas qui vous fera plaisir.</p>
                <a href="menu.php" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-3.5 rounded-xl font-semibold hover:brightness-110 transition-all shadow-md">
                    <span class="material-symbols-outlined">restaurant_menu</span> Voir la carte
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-6">
                <!-- Articles -->
                <div class="space-y-3">
                    <?php foreach ($_SESSION['cart'] as $cartKey => $item): ?>
                        <?php $prixUnitaire = $item['prix'] + ($item['supplement'] ?? 0); ?>
                        <div class="bg-white rounded-2xl border border-outline-variant/30 p-4 flex gap-4 items-center hover:shadow-soft transition-shadow">
                            <a href="plat.php?slug=<?= e($item['slug']) ?>" class="flex-shrink-0">
                                <div class="w-20 h-20 md:w-24 md:h-24 rounded-xl overflow-hidden bg-surface-container-low">
                                    <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($item['image']) ?>"
                                         onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                                         class="w-full h-full object-cover" alt="<?= e($item['nom']) ?>">
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="plat.php?slug=<?= e($item['slug']) ?>" class="block">
                                    <h3 class="font-display font-semibold mb-1 hover:text-primary transition-colors"><?= e($item['nom']) ?></h3>
                                </a>
                                <?php if (!empty($item['options'])): ?>
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        <?php foreach ($item['options'] as $opt): ?>
                                            <span class="text-xs bg-secondary-fixed/40 text-on-secondary-fixed-variant px-2 py-0.5 rounded-full">
                                                + <?= e($opt['nom']) ?><?= $opt['prix'] > 0 ? ' (' . formatPrice($opt['prix']) . ')' : '' ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-sm text-on-surface-variant"><?= formatPrice($prixUnitaire) ?> l'unité</div>
                                <div class="font-bold text-primary mt-1"><?= formatPrice($prixUnitaire * $item['quantite']) ?></div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <form method="post" class="flex items-center bg-surface-container-low rounded-xl overflow-hidden">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_key" value="<?= e($cartKey) ?>">
                                    <button type="submit" name="quantite" value="<?= $item['quantite'] - 1 ?>" class="w-9 h-9 hover:bg-surface-container-high">
                                        <span class="material-symbols-outlined text-base">remove</span>
                                    </button>
                                    <span class="w-8 text-center font-bold"><?= $item['quantite'] ?></span>
                                    <button type="submit" name="quantite" value="<?= $item['quantite'] + 1 ?>" class="w-9 h-9 hover:bg-surface-container-high">
                                        <span class="material-symbols-outlined text-base">add</span>
                                    </button>
                                </form>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?= e($cartKey) ?>">
                                    <button type="submit" class="text-error text-xs hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-base">delete</span> Retirer
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="flex justify-between items-center pt-4">
                        <a href="menu.php" class="text-primary font-semibold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">arrow_back</span> Continuer mes achats
                        </a>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" onclick="return confirm('Vider le panier ?')" class="text-on-surface-variant hover:text-error text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">delete_sweep</span> Vider le panier
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Récap -->
                <aside class="lg:sticky lg:top-24 self-start">
                    <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                        <h2 class="font-display font-semibold text-xl mb-4">Récapitulatif</h2>

                        <!-- Code promo -->
                        <?php if (!$promo): ?>
                        <form method="post" class="mb-4 flex gap-2">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="promo">
                            <input type="text" name="code" placeholder="Code promo" class="flex-1 px-3 py-2 rounded-lg border border-outline-variant text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <button type="submit" class="px-4 py-2 bg-secondary text-white rounded-lg text-sm font-semibold hover:brightness-110">Appliquer</button>
                        </form>
                        <?php else: ?>
                        <div class="bg-tertiary-fixed text-on-tertiary-fixed-variant rounded-lg p-3 mb-4 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-sm">🎉 <?= e($promo['code']) ?></div>
                                <div class="text-xs"><?= e($promo['description']) ?></div>
                            </div>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="promo">
                                <input type="hidden" name="code" value="REMOVE">
                                <?php unset($_SESSION['promo']); ?>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div class="space-y-2 mb-4 pb-4 border-b border-outline-variant/30">
                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">Sous-total</span>
                                <span class="font-semibold"><?= formatPrice($total) ?></span>
                            </div>
                            <?php if ($reduction > 0): ?>
                            <div class="flex justify-between text-sm text-tertiary">
                                <span>Réduction</span>
                                <span class="font-semibold">-<?= formatPrice($reduction) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">delivery_dining</span> Livraison
                                </span>
                                <span class="font-semibold"><?= formatPrice($fraisLivraison) ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-baseline mb-6">
                            <span class="font-display font-semibold text-lg">Total</span>
                            <span class="font-display text-3xl font-bold text-primary"><?= formatPrice($grandTotal) ?></span>
                        </div>

                        <?php if ($total < COMMANDE_MIN): ?>
                            <div class="bg-secondary-fixed text-on-secondary-fixed rounded-lg p-3 mb-4 text-sm flex items-start gap-2">
                                <span class="material-symbols-outlined text-base mt-0.5">info</span>
                                <span>Commande minimum : <?= formatPrice(COMMANDE_MIN) ?>. Il vous manque <?= formatPrice(COMMANDE_MIN - $total) ?>.</span>
                            </div>
                        <?php endif; ?>

                        <a href="paiement.php" class="<?= $total < COMMANDE_MIN ? 'pointer-events-none opacity-50' : '' ?> block w-full bg-primary text-white text-center py-3.5 rounded-xl font-semibold hover:brightness-110 active:scale-[0.98] transition-all shadow-md flex items-center justify-center gap-2">
                            Passer commande <span class="material-symbols-outlined">arrow_forward</span>
                        </a>

                        <div class="mt-4 flex items-center justify-center gap-3 text-xs text-on-surface-variant">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span> Sécurisé</span>
                            <span>·</span>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">delivery_dining</span> ~45 min</span>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
