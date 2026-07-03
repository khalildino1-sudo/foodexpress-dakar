<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Plats vedettes
$stmt = $pdo->query('SELECT p.*, c.nom AS categorie FROM plats p JOIN categories c ON p.categorie_id = c.id WHERE p.vedette = 1 AND p.disponible = 1 ORDER BY p.nb_ventes DESC LIMIT 6');
$vedettes = $stmt->fetchAll();

// Catégories actives
$stmt = $pdo->query('SELECT * FROM categories WHERE actif = 1 ORDER BY ordre LIMIT 6');
$categories = $stmt->fetchAll();

// Avis approuvés
$stmt = $pdo->query('SELECT a.*, u.prenom, u.nom, u.quartier FROM avis a JOIN users u ON a.user_id = u.id WHERE a.approuve = 1 ORDER BY a.created_at DESC LIMIT 3');
$avis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?= APP_NAME ?> · <?= APP_TAGLINE ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-surface">
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <!-- Hero immersif Thieboudienne -->
    <section class="relative w-full min-h-[640px] md:min-h-[760px] flex items-center px-4 md:px-8 py-12 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover brightness-50" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s" alt="Thieboudienne">
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto w-full">
            <div class="max-w-2xl animate-fade-in">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-primary text-white rounded-full font-semibold text-sm mb-6 shadow-lg">
                    <span class="material-symbols-outlined text-base icon-fill">local_fire_department</span>
                    Le Goût Authentique du Sénégal
                </span>
                <h1 class="font-display text-4xl md:text-6xl font-bold text-white mb-6 leading-[1.1]">
                    Le meilleur <span class="text-primary-fixed-dim italic">Thieboudienne</span> de Dakar, livré chez vous.
                </h1>
                <p class="text-lg text-white/90 mb-10 max-w-lg leading-relaxed">Retrouve les saveurs légendaires de la Teranga. Des plats cuisinés avec amour selon les recettes traditionnelles les plus nobles.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="menu.php" class="bg-primary text-white px-8 py-4 rounded-xl font-semibold text-lg hover:brightness-110 transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2 group">
                        Commander mon Ceebu Jën
                        <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </a>
                    <a href="menu.php" class="glass text-on-surface border border-white/30 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white transition-all active:scale-95 text-center">
                        Découvrir la carte
                    </a>
                </div>

                <!-- Stats inline -->
                <div class="flex gap-6 md:gap-10 mt-10 pt-8 border-t border-white/20">
                    <div>
                        <div class="font-display text-3xl font-bold text-white">45<span class="text-primary-fixed-dim">min</span></div>
                        <div class="text-sm text-white/70">Livraison max</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl font-bold text-white">100<span class="text-primary-fixed-dim">%</span></div>
                        <div class="text-sm text-white/70">Sénégalais</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl font-bold text-white">4.9<span class="text-primary-fixed-dim">★</span></div>
                        <div class="text-sm text-white/70">2 400+ avis</div>
                    </div>
                </div>
            </div>

            <!-- Card flottante : livraison express -->
            <div class="hidden xl:block absolute right-8 bottom-8 glass p-6 rounded-2xl border border-white/30 shadow-2xl max-w-xs animate-slide-up">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 bg-secondary-container rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-secondary-container icon-fill">delivery_dining</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm">Livraison Express</h4>
                        <p class="text-xs opacity-70">Plateau · Almadies · Point E</p>
                    </div>
                </div>
                <p class="text-sm italic leading-relaxed text-on-surface">« Enfin un service qui respecte le goût du vrai Thieboudienne ! »</p>
                <div class="flex gap-1 mt-3 text-secondary">
                    <span class="material-symbols-outlined text-base icon-fill">star</span>
                    <span class="material-symbols-outlined text-base icon-fill">star</span>
                    <span class="material-symbols-outlined text-base icon-fill">star</span>
                    <span class="material-symbols-outlined text-base icon-fill">star</span>
                    <span class="material-symbols-outlined text-base icon-fill">star</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Catégories -->
    <section class="py-16 px-4 md:px-8 max-w-7xl mx-auto">
        <div class="text-center mb-10">
            <p class="text-primary font-semibold text-sm uppercase tracking-wider mb-2">Explore notre cuisine</p>
            <h2 class="font-display text-3xl md:text-4xl font-bold">Que désire ton palais ?</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="menu.php?categorie=<?= e($cat['slug']) ?>" class="group bg-white border border-outline-variant/30 rounded-2xl p-6 text-center hover:shadow-hover hover:border-primary transition-all hover:-translate-y-1">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-primary-fixed flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                        <span class="material-symbols-outlined icon-fill text-2xl text-primary group-hover:text-white"><?= e($cat['icone']) ?></span>
                    </div>
                    <h3 class="font-semibold text-sm leading-tight"><?= e($cat['nom']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Plats vedettes -->
    <section class="py-16 px-4 md:px-8 max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-10">
            <div>
                <p class="text-primary font-semibold text-sm uppercase tracking-wider mb-2">Sélection du chef</p>
                <h2 class="font-display text-3xl md:text-4xl font-bold mb-2">Nos Incontournables</h2>
                <p class="text-on-surface-variant">Sélectionnés et préparés avec amour pour vous.</p>
            </div>
            <a href="menu.php" class="hidden sm:flex items-center gap-1 text-primary font-semibold hover:underline">
                Voir toute la carte <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($vedettes as $plat): ?>
                <a href="plat.php?slug=<?= e($plat['slug']) ?>" class="group block bg-white rounded-2xl shadow-soft hover:shadow-hover transition-all border border-outline-variant/30 overflow-hidden hover:-translate-y-1">
                    <div class="aspect-[4/3] overflow-hidden bg-surface-container-low relative">
                        <img src="<?= APP_URL ?>/assets/uploads/plats/<?= e($plat['image']) ?>"
                             onerror="this.src='https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s'"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?= e($plat['nom']) ?>">
                        <?php if ($plat['epice']): ?>
                            <span class="absolute top-3 left-3 bg-error text-white text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm icon-fill">local_fire_department</span> Épicé
                            </span>
                        <?php endif; ?>
                        <span class="absolute top-3 right-3 glass text-on-surface text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-secondary icon-fill">star</span> <?= number_format($plat['note_moyenne'], 1) ?>
                        </span>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-tertiary uppercase tracking-wider"><?= e($plat['categorie']) ?></span>
                            <span class="text-xs text-on-surface-variant flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">schedule</span> <?= $plat['temps_preparation'] ?> min
                            </span>
                        </div>
                        <h3 class="font-display font-semibold text-lg mb-1.5 group-hover:text-primary transition-colors"><?= e($plat['nom']) ?></h3>
                        <p class="text-sm text-on-surface-variant mb-4 line-clamp-2"><?= e($plat['description']) ?></p>
                        <div class="flex justify-between items-center">
                            <span class="font-display text-xl font-bold text-primary"><?= formatPrice($plat['prix']) ?></span>
                            <form method="post" action="panier.php" onclick="event.stopPropagation()">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="plat_id" value="<?= $plat['id'] ?>">
                                <button type="submit" class="p-2.5 bg-primary text-white rounded-xl hover:scale-110 transition-transform shadow-md">
                                    <span class="material-symbols-outlined">add_shopping_cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-10 sm:hidden">
            <a href="menu.php" class="inline-flex items-center gap-1 text-primary font-semibold">
                Voir toute la carte <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
    </section>

    <!-- Bannière promo Degg Na -->
    <section class="px-4 md:px-8 mb-20 max-w-7xl mx-auto">
        <div class="bg-gradient-to-br from-primary via-primary-container to-primary rounded-2xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-8 overflow-hidden relative shadow-2xl">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-secondary-fixed/20 rounded-full blur-3xl"></div>
            <div class="relative z-10 max-w-lg text-white">
                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur rounded-full text-xs font-bold tracking-wider mb-4">OFFRE DE BIENVENUE</span>
                <h2 class="font-display text-4xl md:text-5xl font-bold mb-4">Degg Na !</h2>
                <p class="text-lg opacity-95 mb-8 leading-relaxed">Profite de <span class="font-bold text-4xl">-20%</span> sur ta première commande à Dakar avec le code <span class="bg-white text-primary px-3 py-1 rounded-lg font-bold tracking-wider">DAKAR20</span></p>
                <a href="menu.php" class="bg-white text-primary px-8 py-3 rounded-xl font-semibold hover:bg-surface-container-low transition-colors shadow-lg inline-flex items-center gap-2">
                    J'en profite <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
            <div class="relative z-10 w-full md:w-1/3">
                <img class="w-full h-auto rounded-2xl shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA3PUFypMCNaW2lL3HpGHHMddyabIZcx6dEFlrHHNMXLfPFmFYAPFKVnNnwMPCDFlVapGvvDJ2GMbkkRrDhUjB8vFNmiqTqj9yZBvjfFhd7UxvsfJ_-l9hPh70oHPl9VMH1T08RvvN0cl8slrhudGITrqdUjNVppLTnm3_urtZE2zepovq64-FKU7X4g9oX5qJQLlvYdX6QkQaHf8gcyfeiSH5-NdceBe8FskehOgkWP3ewUf5Wl6SdrgWySEnPPiPg9wUcvCEjaiY" alt="Yassa Poulet">
            </div>
        </div>
    </section>

    <!-- Comment ça marche -->
    <section class="py-16 px-4 md:px-8 max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <p class="text-primary font-semibold text-sm uppercase tracking-wider mb-2">Simple, rapide, savoureux</p>
            <h2 class="font-display text-3xl md:text-4xl font-bold">Comment ça marche ?</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            $steps = [
                ['1', 'menu_book', 'Choisis tes plats', 'Parcours notre carte et compose ton repas selon tes envies.'],
                ['2', 'shopping_cart_checkout', 'Passe commande', 'Paye en espèces, Wave ou Orange Money. C\'est rapide et sécurisé.'],
                ['3', 'delivery_dining', 'Régale-toi', 'On t\'apporte tout, chaud et prêt à déguster en moins de 45 min.'],
            ];
            foreach ($steps as $s): ?>
                <div class="bg-white rounded-2xl p-8 shadow-soft hover:shadow-card transition-all border border-outline-variant/30 relative group">
                    <span class="absolute -top-4 -right-2 font-display text-7xl font-bold text-primary-fixed group-hover:text-primary-fixed-dim transition-colors">0<?= $s[0] ?></span>
                    <div class="w-14 h-14 rounded-2xl bg-primary text-white flex items-center justify-center mb-4 relative z-10 group-hover:rotate-6 transition-transform">
                        <span class="material-symbols-outlined text-2xl"><?= $s[1] ?></span>
                    </div>
                    <h3 class="font-display font-semibold text-xl mb-2 relative z-10"><?= $s[2] ?></h3>
                    <p class="text-on-surface-variant text-sm relative z-10"><?= $s[3] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Témoignages -->
    <?php if (!empty($avis)): ?>
    <section class="py-20 bg-surface-container-low px-4 md:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <p class="text-primary font-semibold text-sm uppercase tracking-wider mb-2">Témoignages</p>
                <h2 class="font-display text-3xl md:text-4xl font-bold mb-4">Ils nous font confiance à Dakar</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($avis as $a): ?>
                    <div class="bg-white p-8 rounded-2xl shadow-soft border border-outline-variant/20 relative">
                        <span class="material-symbols-outlined text-primary text-6xl opacity-15 absolute top-4 right-6">format_quote</span>
                        <div class="flex gap-1 mb-4 text-secondary">
                            <?php for ($i = 0; $i < (int)$a['note']; $i++): ?>
                                <span class="material-symbols-outlined icon-fill">star</span>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-6 italic leading-relaxed">« <?= e($a['commentaire']) ?> »</p>
                        <div class="flex items-center gap-3 pt-4 border-t border-outline-variant/20">
                            <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                <?= strtoupper(substr($a['prenom'], 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="font-semibold text-sm"><?= e($a['prenom'] . ' ' . $a['nom']) ?></h5>
                                <p class="text-xs text-on-surface-variant"><?= e($a['quartier'] ?? 'Dakar') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA final -->
    <section class="py-20 px-4 md:px-8 max-w-5xl mx-auto text-center">
        <h2 class="font-display text-3xl md:text-5xl font-bold mb-4">Votre Teranga sur mobile</h2>
        <p class="text-lg text-on-surface-variant mb-8 max-w-2xl mx-auto">Commandez vos plats préférés, suivez votre livreur en temps réel dans les rues de Dakar et profitez d'offres exclusives.</p>
        <a href="menu.php" class="inline-flex items-center gap-2 bg-primary text-white px-10 py-4 rounded-xl font-semibold text-lg hover:brightness-110 transition-all shadow-lg">
            Commander maintenant <span class="material-symbols-outlined">arrow_forward</span>
        </a>
    </section>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
