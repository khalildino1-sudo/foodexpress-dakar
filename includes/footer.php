<?php
/**
 * Footer client
 */
?>
<footer class="bg-inverse-surface text-inverse-on-surface mt-20">
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Logo + description -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
                        <span class="material-symbols-outlined icon-fill">restaurant</span>
                    </div>
                    <div>
                        <div class="font-display font-bold text-lg">FoodExpress</div>
                        <div class="text-xs opacity-70 -mt-0.5">Dakar · Sénégal</div>
                    </div>
                </div>
                <p class="text-sm opacity-80 mb-4">Le goût authentique du Sénégal, livré chez vous en moins de 45 minutes. Teranga, qualité et passion.</p>
                <div class="flex gap-3">
                    <a href="#" class="w-9 h-9 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-base">facebook</span>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-base">photo_camera</span>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-base">chat</span>
                    </a>
                </div>
            </div>

            <!-- Liens -->
            <div>
                <h4 class="font-display font-semibold text-base mb-4">Navigation</h4>
                <ul class="space-y-2 text-sm opacity-80">
                    <li><a href="<?= APP_URL ?>/client/index.php" class="hover:text-primary-fixed-dim transition-colors">Accueil</a></li>
                    <li><a href="<?= APP_URL ?>/client/menu.php" class="hover:text-primary-fixed-dim transition-colors">Notre menu</a></li>
                    <li><a href="<?= APP_URL ?>/client/menu.php?vedette=1" class="hover:text-primary-fixed-dim transition-colors">Nos incontournables</a></li>
                    <li><a href="<?= APP_URL ?>/client/contact.php" class="hover:text-primary-fixed-dim transition-colors">Nous contacter</a></li>
                </ul>
            </div>

            <!-- Service -->
            <div>
                <h4 class="font-display font-semibold text-base mb-4">Service client</h4>
                <ul class="space-y-2 text-sm opacity-80">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-base">phone</span> <?= APP_PHONE ?></li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-base">mail</span> <?= APP_EMAIL ?></li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-base">schedule</span> Tous les jours 8h - 23h</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-base">location_on</span> Plateau, Dakar</li>
                </ul>
            </div>

            <!-- Zones livraison -->
            <div>
                <h4 class="font-display font-semibold text-base mb-4">Zones de livraison</h4>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_slice(QUARTIERS_DAKAR, 0, 10) as $q): ?>
                        <span class="text-xs px-3 py-1 bg-white/10 rounded-full"><?= e($q) ?></span>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs opacity-60 mt-3">Livraison express dans tout Dakar à partir de <?= formatPrice(FRAIS_LIVRAISON) ?>.</p>
            </div>
        </div>

        <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4 text-sm opacity-70">
            <p>© <?= date('Y') ?> FoodExpress Dakar. Tous droits réservés.</p>
            <p class="flex items-center gap-2">Fait avec <span class="material-symbols-outlined text-primary-fixed-dim text-base icon-fill">favorite</span> à Dakar</p>
        </div>
    </div>
</footer>
