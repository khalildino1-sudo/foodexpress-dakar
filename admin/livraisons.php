<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        $commandeId = (int)($_POST['commande_id'] ?? 0);

        if ($action === 'assigner') {
            $livreur = trim($_POST['livreur'] ?? '');
            $tel     = trim($_POST['telephone_livreur'] ?? '');
            $statut  = $_POST['statut'] ?? 'assignee';

            // Crée la livraison si elle n'existe pas
            $check = $pdo->prepare('SELECT id FROM livraisons WHERE commande_id = ?');
            $check->execute([$commandeId]);
            if ($check->fetch()) {
                $pdo->prepare('UPDATE livraisons SET livreur=?, telephone_livreur=?, statut=? WHERE commande_id=?')
                    ->execute([$livreur, $tel, $statut, $commandeId]);
            } else {
                $pdo->prepare('INSERT INTO livraisons (commande_id, livreur, telephone_livreur, statut) VALUES (?,?,?,?)')
                    ->execute([$commandeId, $livreur, $tel, $statut]);
            }

            // Synchronise statut commande
            if ($statut === 'en_route') {
                $pdo->prepare("UPDATE commandes SET statut='en_livraison' WHERE id=?")->execute([$commandeId]);
                $pdo->prepare("UPDATE livraisons SET heure_depart=NOW() WHERE commande_id=? AND heure_depart IS NULL")->execute([$commandeId]);
            } elseif ($statut === 'livree') {
                $pdo->prepare("UPDATE commandes SET statut='livree' WHERE id=?")->execute([$commandeId]);
                $pdo->prepare("UPDATE livraisons SET heure_livraison=NOW() WHERE commande_id=?")->execute([$commandeId]);
                $pdo->prepare("UPDATE paiements SET statut='valide' WHERE commande_id=?")->execute([$commandeId]);
            }
            setFlash('success', 'Livraison mise à jour.');
        }
    }
    redirect('livraisons.php');
}

// Commandes nécessitant une livraison
$livraisons = $pdo->query("
    SELECT c.id AS commande_id, c.numero, c.adresse_livraison, c.quartier, c.telephone, c.total, c.statut AS statut_cmd,
           u.prenom, u.nom,
           l.id AS livraison_id, l.livreur, l.telephone_livreur, l.statut AS statut_liv, l.heure_depart
    FROM commandes c
    JOIN users u ON u.id = c.user_id
    LEFT JOIN livraisons l ON l.commande_id = c.id
    WHERE c.statut IN ('confirmee','en_preparation','en_livraison','livree')
    ORDER BY FIELD(c.statut,'en_livraison','en_preparation','confirmee','livree'), c.created_at DESC
")->fetchAll();

$pageTitle = 'Livraisons';
$activeMenu = 'livraisons';
include __DIR__ . '/../includes/admin-sidebar.php';
?>


<!-- Tableau des livraisons -->
<div class="bg-white rounded-2xl shadow-soft border border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-low">
                <tr class="text-left text-on-surface-variant uppercase text-xs tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID Commande</th>
                    <th class="py-3 px-4 font-semibold">Client</th>
                    <th class="py-3 px-4 font-semibold">Livreur</th>
                    <th class="py-3 px-4 font-semibold">Destination</th>
                    <th class="py-3 px-4 font-semibold">Temps écoulé</th>
                    <th class="py-3 px-4 font-semibold">Statut</th>
                    <th class="py-3 px-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($livraisons as $l): ?>
                    <?php
                    $statutLiv = $l['statut_liv'] ?? 'en_attente';
                    $livMap = [
                        'en_attente' => ['À assigner', 'bg-secondary-container text-on-secondary-container'],
                        'assignee'   => ['Assignée', 'bg-tertiary-fixed text-on-tertiary-fixed'],
                        'en_route'   => ['En route', 'bg-primary-fixed text-on-primary-fixed'],
                        'livree'     => ['Livré', 'bg-tertiary text-on-tertiary'],
                        'echec'      => ['Échec', 'bg-error-container text-on-error-container'],
                    ];
                    [$livLabel, $livCls] = $livMap[$statutLiv] ?? ['—', 'bg-gray-200'];
                    $tempsEcoule = $l['heure_depart'] ? timeAgo($l['heure_depart']) : '—';
                    $adresseComplete = $l['adresse_livraison'] . ', ' . $l['quartier'] . ', Dakar, Sénégal';
                    $mapsKey = GOOGLE_MAPS_API_KEY;
                    if ($mapsKey !== '') {
                        $mapSrc = 'https://www.google.com/maps/embed/v1/place?key=' . urlencode($mapsKey) . '&q=' . urlencode($adresseComplete);
                    } else {
                        $mapSrc = 'https://maps.google.com/maps?q=' . urlencode($adresseComplete) . '&output=embed';
                    }
                    ?>
                    <tr class="border-t border-outline-variant/15 hover:bg-surface-container-low transition-colors">
                        <td class="py-3 px-4 font-mono text-xs font-semibold"><?= e($l['numero']) ?></td>
                        <td class="py-3 px-4">
                            <div class="font-medium"><?= e($l['prenom'] . ' ' . $l['nom']) ?></div>
                            <div class="text-xs text-on-surface-variant"><?= e($l['telephone']) ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <?php if (!empty($l['livreur'])): ?>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-tertiary text-base">delivery_dining</span>
                                    <span><?= e($l['livreur']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-on-surface-variant italic">Non assigné</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-base">location_on</span>
                                <span><?= e($l['quartier']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-on-surface-variant"><?= e($tempsEcoule) ?></td>
                        <td class="py-3 px-4">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $livCls ?>"><?= $livLabel ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick="toggleMap('map-<?= $l['commande_id'] ?>')"
                                        class="p-2 bg-tertiary-fixed text-tertiary rounded-lg hover:brightness-95 transition-all" title="Voir sur la carte">
                                    <span class="material-symbols-outlined text-base">map</span>
                                </button>
                                <button type="button" onclick="toggleRow('edit-<?= $l['commande_id'] ?>')"
                                        class="p-2 bg-primary-fixed text-primary rounded-lg hover:bg-primary-fixed-dim transition-colors" title="Gérer">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Ligne carte Google Maps -->
                    <tr id="map-<?= $l['commande_id'] ?>" class="hidden">
                        <td colspan="7" class="px-4 pb-4 bg-surface-container-low">
                            <div class="rounded-xl overflow-hidden border border-outline-variant/30">
                                <div class="bg-white px-4 py-2 text-xs flex items-center gap-2 border-b border-outline-variant/20">
                                    <span class="material-symbols-outlined text-primary text-base">pin_drop</span>
                                    <span class="font-medium"><?= e($l['adresse_livraison']) ?>, <?= e($l['quartier']) ?></span>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($adresseComplete) ?>" target="_blank"
                                       class="ml-auto text-primary hover:underline flex items-center gap-1">
                                        Ouvrir dans Google Maps <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                                <iframe width="100%" height="280" style="border:0" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        src="<?= e($mapSrc) ?>"></iframe>
                            </div>
                        </td>
                    </tr>
                    <!-- Ligne formulaire d'assignation -->
                    <tr id="edit-<?= $l['commande_id'] ?>" class="hidden">
                        <td colspan="7" class="px-4 pb-4 bg-surface-container-low">
                            <form method="post" class="bg-white rounded-xl border border-outline-variant/30 p-4 flex flex-col md:flex-row gap-3 md:items-end">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="assigner">
                                <input type="hidden" name="commande_id" value="<?= $l['commande_id'] ?>">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium mb-1 text-on-surface-variant">Nom du livreur</label>
                                    <input type="text" name="livreur" value="<?= e($l['livreur'] ?? '') ?>" placeholder="Ex : Modou Sène"
                                           class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/40 rounded-lg text-sm focus:border-primary">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium mb-1 text-on-surface-variant">Téléphone livreur</label>
                                    <input type="tel" name="telephone_livreur" value="<?= e($l['telephone_livreur'] ?? '') ?>" placeholder="+221 ..."
                                           class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/40 rounded-lg text-sm focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1 text-on-surface-variant">Statut</label>
                                    <select name="statut" class="px-3 py-2 bg-surface-container-low border border-outline-variant/40 rounded-lg text-sm focus:border-primary">
                                        <option value="assignee" <?= $statutLiv === 'assignee' ? 'selected' : '' ?>>Assignée</option>
                                        <option value="en_route" <?= $statutLiv === 'en_route' ? 'selected' : '' ?>>En route</option>
                                        <option value="livree" <?= $statutLiv === 'livree' ? 'selected' : '' ?>>Livré</option>
                                        <option value="echec" <?= $statutLiv === 'echec' ? 'selected' : '' ?>>Échec</option>
                                    </select>
                                </div>
                                <button class="px-5 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:brightness-110 active:scale-95 transition-all flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">save</span> Enregistrer
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$livraisons): ?>
                    <tr><td colspan="7" class="py-12 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-5xl block mb-2">local_shipping</span>
                        Aucune livraison en cours.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleRow(id) {
        document.getElementById(id).classList.toggle('hidden');
    }
    function toggleMap(id) {
        document.getElementById(id).classList.toggle('hidden');
    }
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
