<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

requireLogin();

if (empty($_SESSION['cart'])) {
    setFlash('error', 'Votre panier est vide.');
    redirect('menu.php');
}

// Récupérer l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$total = cartTotal();
$promo = $_SESSION['promo'] ?? null;
$reduction = 0;
if ($promo && $total >= $promo['montant_min']) {
    $reduction = $promo['type'] === 'pourcentage' ? ($total * $promo['valeur'] / 100) : $promo['valeur'];
}
$fraisLivraison = FRAIS_LIVRAISON;
$grandTotal = max(0, $total - $reduction + $fraisLivraison);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $adresse  = trim($_POST['adresse'] ?? '');
        $quartier = trim($_POST['quartier'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $methode  = $_POST['methode_paiement'] ?? 'especes';
        $instructions = trim($_POST['instructions'] ?? '');

        if (strlen($adresse) < 5) $errors[] = 'Adresse de livraison requise.';
        if (!in_array($quartier, QUARTIERS_DAKAR)) $errors[] = 'Quartier de livraison invalide.';
        if (strlen($telephone) < 8) $errors[] = 'Numéro de téléphone requis.';
        if (!in_array($methode, ['especes', 'wave', 'orange_money', 'carte'])) $errors[] = 'Méthode de paiement invalide.';
        if ($total < COMMANDE_MIN) $errors[] = 'Commande minimum non atteinte.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $numero = generateOrderNumber($pdo);
                $stmt = $pdo->prepare('INSERT INTO commandes (numero, user_id, sous_total, frais_livraison, reduction, code_promo, total, adresse_livraison, quartier, telephone, instructions, statut, methode_paiement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $numero, $user['id'], $total, $fraisLivraison, $reduction,
                    $promo['code'] ?? null, $grandTotal, $adresse, $quartier,
                    $telephone, $instructions, 'en_attente', $methode
                ]);
                $commandeId = $pdo->lastInsertId();

                $stmtDetail = $pdo->prepare('INSERT INTO details_commandes (commande_id, plat_id, nom_plat, prix_unitaire, quantite, sous_total, options) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmtVente  = $pdo->prepare('UPDATE plats SET nb_ventes = nb_ventes + ? WHERE id = ?');
                $items = [];
                foreach ($_SESSION['cart'] as $item) {
                    $prixUnitaire = $item['prix'] + ($item['supplement'] ?? 0);
                    $sousTotal = $prixUnitaire * $item['quantite'];
                    // Texte des options pour la commande
                    $optionsTxt = null;
                    if (!empty($item['options'])) {
                        $optionsTxt = implode(', ', array_map(fn($o) => $o['nom'], $item['options']));
                    }
                    $stmtDetail->execute([$commandeId, $item['plat_id'], $item['nom'], $prixUnitaire, $item['quantite'], $sousTotal, $optionsTxt]);
                    $stmtVente->execute([$item['quantite'], $item['plat_id']]);
                    $items[] = ['nom_plat' => $item['nom'], 'quantite' => $item['quantite'], 'sous_total' => $sousTotal];
                }

                $stmt = $pdo->prepare('INSERT INTO paiements (commande_id, methode, montant, statut) VALUES (?, ?, ?, ?)');
                $stmt->execute([$commandeId, $methode, $grandTotal, $methode === 'especes' ? 'en_attente' : 'valide']);

                $stmt = $pdo->prepare('INSERT INTO livraisons (commande_id, statut) VALUES (?, "en_attente")');
                $stmt->execute([$commandeId]);

                $pdo->commit();

                // Email de confirmation (échec silencieux si PHPMailer non configuré)
                $commande = [
                    'numero'          => $numero,
                    'sous_total'      => $total,
                    'frais_livraison' => $fraisLivraison,
                    'reduction'       => $reduction,
                    'total'           => $grandTotal,
                    'adresse_livraison' => $adresse,
                    'quartier'        => $quartier,
                    'telephone'       => $telephone,
                ];
                @sendOrderConfirmation($commande, $user, $items);

                // Reset panier + promo
                $_SESSION['cart'] = [];
                unset($_SESSION['promo']);

                setFlash('success', 'Commande ' . $numero . ' confirmée ! Suivez sa progression ici.');
                redirect('suivi.php?num=' . urlencode($numero));
            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = 'Erreur lors de la création de la commande : ' . $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paiement - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <div class="mb-8">
            <h1 class="font-display text-3xl md:text-4xl font-bold mb-2">Finaliser la commande</h1>
            <p class="text-on-surface-variant">Plus que quelques étapes avant de te régaler 🍽️</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-error-container text-on-error-container rounded-xl p-4 mb-6 flex items-start gap-3">
                <span class="material-symbols-outlined">error</span>
                <div class="text-sm">
                    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-6">
            <?= csrfField() ?>
            <div class="space-y-6">
                <!-- Étape 1 : Livraison -->
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold">1</div>
                        <h2 class="font-display font-semibold text-xl">Adresse de livraison</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Adresse complète</label>
                            <textarea name="adresse" rows="2" required class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Ex: Avenue Bourguiba, Immeuble Khadim, Apt 3B"><?= e($user['adresse'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Quartier</label>
                            <select name="quartier" required class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                                <option value="">Choisir un quartier...</option>
                                <?php foreach (QUARTIERS_DAKAR as $q): ?>
                                    <option <?= $q === ($user['quartier'] ?? '') ? 'selected' : '' ?>><?= e($q) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Téléphone</label>
                            <input type="tel" name="telephone" value="<?= e($user['telephone'] ?? '') ?>" required placeholder="+221 77 ..." class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Instructions de livraison <span class="text-on-surface-variant font-normal">(facultatif)</span></label>
                            <textarea name="instructions" rows="2" placeholder="Ex: Étage 3, sonner deux fois..." class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Étape 2 : Paiement -->
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold">2</div>
                        <h2 class="font-display font-semibold text-xl">Méthode de paiement</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="methode_paiement" value="especes" checked class="peer sr-only">
                            <div class="border-2 border-outline-variant rounded-xl p-4 flex items-center gap-3 peer-checked:border-primary peer-checked:bg-primary-fixed transition-all">
                                <div class="w-12 h-12 rounded-xl bg-tertiary-fixed flex items-center justify-center">
                                    <span class="material-symbols-outlined text-on-tertiary-fixed text-2xl">payments</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Espèces</div>
                                    <div class="text-xs text-on-surface-variant">À la livraison</div>
                                </div>
                                <span class="material-symbols-outlined text-primary opacity-0 peer-checked:opacity-100">check_circle</span>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="methode_paiement" value="wave" class="peer sr-only">
                            <div class="border-2 border-outline-variant rounded-xl p-4 flex items-center gap-3 peer-checked:border-primary peer-checked:bg-primary-fixed transition-all">
                                <div class="w-12 h-12 rounded-xl bg-[#21B6F5] flex items-center justify-center text-white font-bold text-xs">Wave</div>
                                <div class="flex-1">
                                    <div class="font-semibold">Wave</div>
                                    <div class="text-xs text-on-surface-variant">Paiement mobile rapide</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="methode_paiement" value="orange_money" class="peer sr-only">
                            <div class="border-2 border-outline-variant rounded-xl p-4 flex items-center gap-3 peer-checked:border-primary peer-checked:bg-primary-fixed transition-all">
                                <div class="w-12 h-12 rounded-xl bg-[#FF7900] flex items-center justify-center text-white font-bold text-xs">OM</div>
                                <div class="flex-1">
                                    <div class="font-semibold">Orange Money</div>
                                    <div class="text-xs text-on-surface-variant">Paiement mobile</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="methode_paiement" value="carte" class="peer sr-only">
                            <div class="border-2 border-outline-variant rounded-xl p-4 flex items-center gap-3 peer-checked:border-primary peer-checked:bg-primary-fixed transition-all">
                                <div class="w-12 h-12 rounded-xl bg-secondary flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-2xl">credit_card</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Carte bancaire</div>
                                    <div class="text-xs text-on-surface-variant">Visa, Mastercard</div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="bg-tertiary-fixed text-on-tertiary-fixed-variant rounded-lg p-3 mt-4 text-sm flex items-start gap-2">
                        <span class="material-symbols-outlined text-base mt-0.5">verified_user</span>
                        <span>Paiement 100% sécurisé. Tes données sont protégées.</span>
                    </div>
                </div>
            </div>

            <!-- Récap -->
            <aside class="lg:sticky lg:top-24 self-start">
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 shadow-soft">
                    <h2 class="font-display font-semibold text-xl mb-4">Votre commande</h2>

                    <div class="space-y-3 mb-4 pb-4 border-b border-outline-variant/30 max-h-64 overflow-y-auto">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="flex gap-3 items-center">
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-surface-container-low flex-shrink-0">
                                    <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($item['image']) ?>"
                                         onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                                         class="w-full h-full object-cover" alt="">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold truncate"><?= e($item['nom']) ?></div>
                                    <div class="text-xs text-on-surface-variant">× <?= $item['quantite'] ?></div>
                                    <?php if (!empty($item['options'])): ?>
                                        <div class="text-xs text-tertiary truncate">+ <?= e(implode(', ', array_map(fn($o) => $o['nom'], $item['options']))) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm font-semibold"><?= formatPrice(($item['prix'] + ($item['supplement'] ?? 0)) * $item['quantite']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-2 mb-4 pb-4 border-b border-outline-variant/30">
                        <div class="flex justify-between text-sm">
                            <span class="text-on-surface-variant">Sous-total</span>
                            <span class="font-semibold"><?= formatPrice($total) ?></span>
                        </div>
                        <?php if ($reduction > 0): ?>
                        <div class="flex justify-between text-sm text-tertiary">
                            <span>Réduction (<?= e($promo['code']) ?>)</span>
                            <span class="font-semibold">-<?= formatPrice($reduction) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-on-surface-variant">Livraison</span>
                            <span class="font-semibold"><?= formatPrice($fraisLivraison) ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-baseline mb-6">
                        <span class="font-display font-semibold text-lg">Total</span>
                        <span class="font-display text-3xl font-bold text-primary"><?= formatPrice($grandTotal) ?></span>
                    </div>

                    <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-semibold hover:brightness-110 active:scale-[0.98] transition-all shadow-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">lock</span> Confirmer la commande
                    </button>

                    <a href="panier.php" class="block text-center mt-3 text-sm text-on-surface-variant hover:text-primary">
                        ← Modifier le panier
                    </a>
                </div>
            </aside>
        </form>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
