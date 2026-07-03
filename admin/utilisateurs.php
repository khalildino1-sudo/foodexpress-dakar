<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        $uid = (int)($_POST['id'] ?? 0);

        if ($action === 'role' && $uid !== (int)$_SESSION['user_id']) {
            $newRole = $_POST['role'] ?? 'client';
            if (in_array($newRole, ['client', 'admin'])) {
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $uid]);
                setFlash('success', 'Rôle de l\'utilisateur mis à jour.');
            }
        }

        if ($action === 'delete' && $uid !== (int)$_SESSION['user_id']) {
            try {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                setFlash('success', 'Utilisateur supprimé.');
            } catch (PDOException $e) {
                setFlash('error', 'Impossible de supprimer cet utilisateur.');
            }
        }
    }
    redirect('utilisateurs.php');
}

$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT u.*, COUNT(c.id) AS nb_commandes, COALESCE(SUM(c.total),0) AS total_depense
        FROM users u LEFT JOIN commandes c ON c.user_id = u.id AND c.statut != 'annulee'
        WHERE 1=1";
$params = [];
if (in_array($roleFilter, ['client', 'admin'])) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}
if ($search !== '') {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$nbClients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$nbAdmins  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

$pageTitle = 'Utilisateurs';
$activeMenu = 'utilisateurs';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Stats rapides -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-tertiary-fixed flex items-center justify-center">
            <span class="material-symbols-outlined text-tertiary icon-fill">group</span>
        </div>
        <div>
            <div class="font-display font-bold text-2xl"><?= $nbClients ?></div>
            <div class="text-sm text-on-surface-variant">Clients</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/20 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center">
            <span class="material-symbols-outlined text-primary icon-fill">admin_panel_settings</span>
        </div>
        <div>
            <div class="font-display font-bold text-2xl"><?= $nbAdmins ?></div>
            <div class="text-sm text-on-surface-variant">Administrateurs</div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white rounded-2xl p-3 shadow-soft border border-outline-variant/20 mb-6 flex flex-col sm:flex-row gap-3">
    <div class="flex gap-1">
        <a href="?role=" class="px-4 py-2 rounded-xl text-sm font-medium <?= $roleFilter === '' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">Tous</a>
        <a href="?role=client" class="px-4 py-2 rounded-xl text-sm font-medium <?= $roleFilter === 'client' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">Clients</a>
        <a href="?role=admin" class="px-4 py-2 rounded-xl text-sm font-medium <?= $roleFilter === 'admin' ? 'bg-primary text-white' : 'hover:bg-surface-container-low' ?>">Admins</a>
    </div>
    <form method="get" class="flex-1 relative">
        <input type="hidden" name="role" value="<?= e($roleFilter) ?>">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant">search</span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher un utilisateur..."
               class="w-full pl-11 pr-4 py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
    </form>
</div>

<!-- Tableau -->
<div class="bg-white rounded-2xl shadow-soft border border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-low">
                <tr class="text-left text-on-surface-variant uppercase text-xs tracking-wider">
                    <th class="py-3 px-4 font-semibold">Nom</th>
                    <th class="py-3 px-4 font-semibold">Email</th>
                    <th class="py-3 px-4 font-semibold">Rôle</th>
                    <th class="py-3 px-4 font-semibold">Date d'inscription</th>
                    <th class="py-3 px-4 font-semibold">Dépense Totale</th>
                    <th class="py-3 px-4 font-semibold">Statut</th>
                    <th class="py-3 px-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php $isMe = (int)$u['id'] === (int)$_SESSION['user_id']; ?>
                    <tr class="border-t border-outline-variant/15 hover:bg-surface-container-low transition-colors">
                        <!-- Nom -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                                    <?= strtoupper(substr($u['prenom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-medium"><?= e($u['prenom'] . ' ' . $u['nom']) ?> <?= $isMe ? '<span class="text-xs text-primary">(vous)</span>' : '' ?></div>
                                    <div class="text-xs text-on-surface-variant"><?= e($u['telephone'] ?: 'Aucun téléphone') ?></div>
                                </div>
                            </div>
                        </td>
                        <!-- Email -->
                        <td class="py-3 px-4 text-on-surface-variant"><?= e($u['email']) ?></td>
                        <!-- Rôle -->
                        <td class="py-3 px-4">
                            <?php if ($isMe): ?>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-primary-fixed text-primary font-medium">Admin</span>
                            <?php else: ?>
                                <form method="post" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <select name="role" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded-lg border border-outline-variant/40 bg-surface-container-low focus:border-primary">
                                        <option value="client" <?= $u['role'] === 'client' ? 'selected' : '' ?>>Client</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <!-- Date d'inscription -->
                        <td class="py-3 px-4 text-on-surface-variant"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <!-- Dépense totale -->
                        <td class="py-3 px-4">
                            <span class="font-semibold text-primary"><?= formatPrice($u['total_depense']) ?></span>
                            <div class="text-xs text-on-surface-variant"><?= $u['nb_commandes'] ?> commande<?= $u['nb_commandes'] > 1 ? 's' : '' ?></div>
                        </td>
                        <!-- Statut -->
                        <td class="py-3 px-4">
                            <?php if ($u['nb_commandes'] > 0): ?>
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-tertiary"></span> Actif
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-surface-container text-on-surface-variant font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-outline"></span> Inactif
                                </span>
                            <?php endif; ?>
                        </td>
                        <!-- Actions -->
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-end">
                                <?php if (!$isMe): ?>
                                    <form method="post" onsubmit="return confirm('Supprimer cet utilisateur et toutes ses donnees ?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button class="p-2 bg-error-container text-error rounded-lg hover:bg-error hover:text-white transition-colors" title="Supprimer">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-variant">â</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr><td colspan="7" class="py-12 text-center text-on-surface-variant">Aucun utilisateur trouve.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
