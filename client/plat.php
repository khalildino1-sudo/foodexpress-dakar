<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT p.*, c.nom AS categorie, c.slug AS categorie_slug FROM plats p JOIN categories c ON p.categorie_id = c.id WHERE p.slug = ? AND p.disponible = 1');
$stmt->execute([$slug]);
$plat = $stmt->fetch();

if (!$plat) {
    setFlash('error', 'Plat introuvable.');
    redirect('menu.php');
}

// Avis du plat
$stmt = $pdo->prepare('SELECT a.*, u.prenom, u.nom FROM avis a JOIN users u ON a.user_id = u.id WHERE a.plat_id = ? AND a.approuve = 1 ORDER BY a.created_at DESC LIMIT 5');
$stmt->execute([$plat['id']]);
$avis = $stmt->fetchAll();

// Plats similaires
$stmt = $pdo->prepare('SELECT p.*, c.nom AS categorie FROM plats p JOIN categories c ON p.categorie_id = c.id WHERE p.categorie_id = ? AND p.id != ? AND p.disponible = 1 ORDER BY p.nb_ventes DESC LIMIT 3');
$stmt->execute([$plat['categorie_id'], $plat['id']]);
$similaires = $stmt->fetchAll();

// Options / suppléments du plat
$stmt = $pdo->prepare('SELECT * FROM options_plats WHERE plat_id = ? AND actif = 1 ORDER BY id');
$stmt->execute([$plat['id']]);
$options = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?= e($plat['nom']) ?> - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-6">
        <!-- Retour au menu -->
        <a href="menu.php" class="inline-flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors">
            <span class="material-symbols-outlined text-base">arrow_back</span> Retour au menu
        </a>
        <!-- Fil d'Ariane -->
        <nav class="text-sm mb-6 flex items-center gap-2 text-on-surface-variant">
            <a href="index.php" class="hover:text-primary">Accueil</a>
            <span class="material-symbols-outlined text-base">chevron_right</span>
            <a href="menu.php" class="hover:text-primary">Carte</a>
            <span class="material-symbols-outlined text-base">chevron_right</span>
            <a href="menu.php?categorie=<?= e($plat['categorie_slug']) ?>" class="hover:text-primary"><?= e($plat['categorie']) ?></a>
            <span class="material-symbols-outlined text-base">chevron_right</span>
            <span class="text-on-surface font-medium truncate"><?= e($plat['nom']) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Image -->
            <div class="relative">
                <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-card bg-surface-container-low">
                    <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($plat['image']) ?>"
                         onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                         class="w-full h-full object-cover" alt="<?= e($plat['nom']) ?>">
                </div>

                <!-- Badges -->
                <div class="absolute top-4 left-4 flex gap-2">
                    <?php if ($plat['vedette']): ?>
                        <span class="bg-secondary text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1 shadow-md">
                            <span class="material-symbols-outlined text-sm icon-fill">star</span> Vedette
                        </span>
                    <?php endif; ?>
                    <?php if ($plat['epice']): ?>
                        <span class="bg-error text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1 shadow-md">
                            <span class="material-symbols-outlined text-sm icon-fill">local_fire_department</span> Épicé
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Infos -->
            <div>
                <span class="inline-block text-xs font-bold text-tertiary uppercase tracking-wider mb-2"><?= e($plat['categorie']) ?></span>
                <h1 class="font-display text-3xl md:text-4xl font-bold mb-3"><?= e($plat['nom']) ?></h1>

                <!-- Note + ventes -->
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="material-symbols-outlined text-secondary <?= $i <= floor($plat['note_moyenne']) ? 'icon-fill' : '' ?>">star</span>
                        <?php endfor; ?>
                        <span class="font-bold ml-1"><?= number_format($plat['note_moyenne'], 1) ?></span>
                    </div>
                    <span class="text-sm text-on-surface-variant"><?= $plat['nb_ventes'] ?> commandes</span>
                </div>

                <p class="text-on-surface-variant text-lg leading-relaxed mb-6"><?= nl2br(e($plat['description'])) ?></p>

                <!-- Infos rapides -->
                <div class="grid grid-cols-3 gap-3 mb-8">
                    <div class="bg-white rounded-xl p-4 border border-outline-variant/30 text-center">
                        <span class="material-symbols-outlined text-primary text-2xl">schedule</span>
                        <div class="text-xs text-on-surface-variant mt-1">Préparation</div>
                        <div class="font-bold"><?= $plat['temps_preparation'] ?> min</div>
                    </div>
                    <?php if ($plat['calories']): ?>
                    <div class="bg-white rounded-xl p-4 border border-outline-variant/30 text-center">
                        <span class="material-symbols-outlined text-primary text-2xl">local_fire_department</span>
                        <div class="text-xs text-on-surface-variant mt-1">Calories</div>
                        <div class="font-bold"><?= $plat['calories'] ?> kcal</div>
                    </div>
                    <?php endif; ?>
                    <div class="bg-white rounded-xl p-4 border border-outline-variant/30 text-center">
                        <span class="material-symbols-outlined text-primary text-2xl">delivery_dining</span>
                        <div class="text-xs text-on-surface-variant mt-1">Livraison</div>
                        <div class="font-bold">~45 min</div>
                    </div>
                </div>

                <!-- Ingrédients -->
                <?php if (!empty($plat['ingredients'])): ?>
                <div class="mb-8">
                    <h3 class="font-display font-semibold mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-primary">restaurant_menu</span> Ingrédients</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (array_filter(array_map('trim', explode(',', $plat['ingredients']))) as $ing): ?>
                            <span class="bg-surface-container-high px-3 py-1.5 rounded-full text-sm"><?= e($ing) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Prix + ajout panier -->
                <div class="bg-gradient-to-br from-primary-fixed to-secondary-fixed/40 rounded-2xl p-6 border border-primary/20">
                    <div class="flex items-end justify-between mb-4">
                        <div>
                            <div class="text-sm text-on-surface-variant mb-1">Prix</div>
                            <div class="font-display text-4xl font-bold text-primary"><?= formatPrice($plat['prix']) ?></div>
                        </div>
                    </div>

                    <form method="post" action="panier.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="plat_id" value="<?= $plat['id'] ?>">

                        <?php if (!empty($options)): ?>
                            <!-- Personnalisation -->
                            <div class="bg-white rounded-xl p-4 mb-4">
                                <h3 class="font-display font-semibold mb-3 flex items-center gap-2 text-sm">
                                    <span class="material-symbols-outlined text-primary text-xl">tune</span> Personnalisez votre plat
                                </h3>
                                <div class="space-y-2">
                                    <?php foreach ($options as $opt): ?>
                                        <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-surface-container-low cursor-pointer transition-colors">
                                            <input type="checkbox" name="options[]" value="<?= $opt['id'] ?>"
                                                   data-prix="<?= $opt['prix_supplement'] ?>"
                                                   onchange="majTotal()"
                                                   class="option-chk rounded text-primary focus:ring-primary w-5 h-5">
                                            <span class="flex-1 text-sm"><?= e($opt['nom']) ?></span>
                                            <span class="text-sm font-medium <?= $opt['prix_supplement'] > 0 ? 'text-primary' : 'text-tertiary' ?>">
                                                <?= $opt['prix_supplement'] > 0 ? '+ ' . formatPrice($opt['prix_supplement']) : 'Offert' ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center bg-white rounded-xl border border-outline-variant overflow-hidden">
                                <button type="button" onclick="qty(-1)" class="w-11 h-11 hover:bg-surface-container-low transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined">remove</span>
                                </button>
                                <input id="qty" type="number" name="quantite" value="1" min="1" max="20" onchange="majTotal()" class="w-12 text-center bg-transparent border-none focus:ring-0 font-bold">
                                <button type="button" onclick="qty(1)" class="w-11 h-11 hover:bg-surface-container-low transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined">add</span>
                                </button>
                            </div>

                            <button type="submit" class="flex-1 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:brightness-110 active:scale-95 transition-all shadow-md flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">add_shopping_cart</span>
                                Ajouter · <span id="totalBtn"><?= formatPrice($plat['prix']) ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Avis -->
        <?php if (!empty($avis)): ?>
        <section class="mt-16">
            <h2 class="font-display text-2xl font-bold mb-6">Avis clients</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($avis as $a): ?>
                    <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 shadow-soft">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                <?= strtoupper(substr($a['prenom'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-semibold text-sm"><?= e($a['prenom'] . ' ' . substr($a['nom'], 0, 1) . '.') ?></div>
                                <div class="flex gap-0.5 text-secondary">
                                    <?php for ($i = 0; $i < $a['note']; $i++): ?>
                                        <span class="material-symbols-outlined text-sm icon-fill">star</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed italic">« <?= e($a['commentaire']) ?> »</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Plats similaires -->
        <?php if (!empty($similaires)): ?>
        <section class="mt-16">
            <h2 class="font-display text-2xl font-bold mb-6">Vous aimerez aussi</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($similaires as $sim): ?>
                    <a href="plat.php?slug=<?= e($sim['slug']) ?>" class="group block bg-white rounded-2xl shadow-soft hover:shadow-hover transition-all border border-outline-variant/30 overflow-hidden">
                        <div class="aspect-[4/3] overflow-hidden bg-surface-container-low">
                            <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($sim['image']) ?>"
                                 onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?= e($sim['nom']) ?>">
                        </div>
                        <div class="p-4">
                            <h3 class="font-display font-semibold mb-2"><?= e($sim['nom']) ?></h3>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-primary"><?= formatPrice($sim['prix']) ?></span>
                                <span class="text-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base text-secondary icon-fill">star</span> <?= number_format($sim['note_moyenne'], 1) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script>
        const prixBase = <?= (float)$plat['prix'] ?>;
        function qty(delta) {
            const input = document.getElementById('qty');
            const v = Math.max(1, Math.min(20, parseInt(input.value || 1) + delta));
            input.value = v;
            majTotal();
        }
        function majTotal() {
            let supplement = 0;
            document.querySelectorAll('.option-chk:checked').forEach(c => {
                supplement += parseFloat(c.dataset.prix || 0);
            });
            const q = parseInt(document.getElementById('qty').value || 1);
            const total = (prixBase + supplement) * q;
            document.getElementById('totalBtn').textContent =
                new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
        }
    </script>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
