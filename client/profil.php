<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$errors = [];
$success = false;

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? 'info';

        if ($action === 'info') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $quartier = trim($_POST['quartier'] ?? '');

            if (empty($nom) || empty($prenom)) $errors[] = 'Nom et prénom requis.';

            if (empty($errors)) {
                $pdo->prepare('UPDATE users SET nom=?, prenom=?, telephone=?, adresse=?, quartier=? WHERE id=?')
                    ->execute([$nom, $prenom, $telephone, $adresse, $quartier, $_SESSION['user_id']]);
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_prenom'] = $prenom;
                setFlash('success', 'Profil mis à jour avec succès.');
                redirect('profil.php');
            }
        } elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!password_verify($current, $user['password_hash'])) $errors[] = 'Mot de passe actuel incorrect.';
            if (strlen($new) < 8) $errors[] = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
            if ($new !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

            if (empty($errors)) {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);
                setFlash('success', 'Mot de passe modifié avec succès.');
                redirect('profil.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mon profil · <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center text-3xl font-bold shadow-lg">
                <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="font-display text-3xl font-bold"><?= e($user['prenom']) ?> <?= e($user['nom']) ?></h1>
                <p class="text-on-surface-variant"><?= e($user['email']) ?> · Membre depuis <?= date('m/Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-error-container text-on-error-container px-4 py-3 rounded-xl mb-6">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Infos perso -->
            <div class="bg-white rounded-2xl p-6 border border-outline-variant/30">
                <h2 class="font-display text-xl font-semibold mb-5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Informations personnelles
                </h2>
                <form method="post" class="space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="info">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Prénom</label>
                            <input type="text" name="prenom" value="<?= e($user['prenom']) ?>" required
                                   class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Nom</label>
                            <input type="text" name="nom" value="<?= e($user['nom']) ?>" required
                                   class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Email</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant bg-surface-container text-on-surface-variant cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Téléphone</label>
                        <input type="tel" name="telephone" value="<?= e($user['telephone']) ?>"
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Adresse</label>
                        <input type="text" name="adresse" value="<?= e($user['adresse']) ?>"
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Quartier</label>
                        <select name="quartier" class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                            <option value="">— Choisir —</option>
                            <?php foreach (QUARTIERS_DAKAR as $q): ?>
                                <option value="<?= e($q) ?>" <?= $user['quartier'] === $q ? 'selected' : '' ?>><?= e($q) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg font-semibold hover:brightness-110 transition-all">
                        Enregistrer les modifications
                    </button>
                </form>
            </div>

            <!-- Mot de passe -->
            <div class="bg-white rounded-2xl p-6 border border-outline-variant/30 h-fit">
                <h2 class="font-display text-xl font-semibold mb-5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">lock</span>
                    Sécurité
                </h2>
                <form method="post" class="space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="password">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Mot de passe actuel</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Nouveau mot de passe</label>
                        <input type="password" name="new_password" required minlength="8"
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                        <p class="text-xs text-on-surface-variant mt-1">Au moins 8 caractères</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">Confirmer</label>
                        <input type="password" name="confirm_password" required minlength="8"
                               class="w-full px-3 py-2.5 rounded-lg border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                    <button type="submit" class="w-full bg-secondary text-white py-2.5 rounded-lg font-semibold hover:bg-secondary-container hover:text-on-secondary-container transition-all">
                        Modifier le mot de passe
                    </button>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
