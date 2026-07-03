<?php
/**
 * Script d'installation
 * Lance http://localhost/foodexpress-dakar/install.php APRÈS avoir importé le SQL
 * pour régénérer les hash des mots de passe de démo.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

$updated = 0;
$errors = [];

// Liste des comptes de démo et leurs vrais mots de passe
$demoAccounts = [
    'admin@foodexpress.sn'  => 'admin123',
    'mouhamed@example.com'  => 'demo123',
    'aissatou@example.com'  => 'demo123',
    'ousmane@example.com'   => 'demo123',
];

foreach ($demoAccounts as $email => $password) {
    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() > 0) $updated++;
    } catch (Exception $e) {
        $errors[] = $email . ' : ' . $e->getMessage();
    }
}

// Vérifier que les dossiers d'upload existent
$uploadDir = __DIR__ . '/assets/uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
if (!is_dir($uploadDir . '/plats')) @mkdir($uploadDir . '/plats', 0775, true);
if (!is_dir($uploadDir . '/avatars')) @mkdir($uploadDir . '/avatars', 0775, true);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Installation - FoodExpress Dakar</title>
    <?php require __DIR__ . '/includes/head.php'; ?>
</head>
<body class="min-h-screen bg-surface-container-low flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-xl shadow-card p-8 md:p-12 animate-slide-up">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-xl bg-primary flex items-center justify-center text-white shadow-md">
                <span class="material-symbols-outlined icon-fill text-3xl">restaurant</span>
            </div>
            <div>
                <h1 class="font-display text-2xl font-bold">FoodExpress Dakar</h1>
                <p class="text-on-surface-variant text-sm">Script d'installation</p>
            </div>
        </div>

        <div class="bg-tertiary text-white rounded-xl p-6 mb-6">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined icon-fill">check_circle</span>
                <h2 class="font-display font-semibold text-lg">Installation réussie</h2>
            </div>
            <p class="text-sm opacity-90"><?= $updated ?> comptes de démonstration régénérés avec succès.</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-error-container text-on-error-container rounded-xl p-4 mb-6">
            <h3 class="font-semibold mb-2">Erreurs :</h3>
            <ul class="list-disc list-inside text-sm">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="border-2 border-dashed border-outline-variant rounded-xl p-6 mb-6">
            <h3 class="font-display font-semibold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">key</span> Comptes de démonstration
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between bg-primary-fixed rounded-lg p-3">
                    <div>
                        <div class="font-semibold text-on-primary-fixed">Administrateur</div>
                        <code class="text-xs text-on-primary-fixed-variant">admin@foodexpress.sn / admin123</code>
                    </div>
                    <span class="material-symbols-outlined text-primary">admin_panel_settings</span>
                </div>
                <div class="flex items-center justify-between bg-surface-container-low rounded-lg p-3">
                    <div>
                        <div class="font-semibold">Clients démo</div>
                        <code class="text-xs text-on-surface-variant">mouhamed@example.com / demo123</code><br>
                        <code class="text-xs text-on-surface-variant">aissatou@example.com / demo123</code>
                    </div>
                    <span class="material-symbols-outlined text-on-surface-variant">person</span>
                </div>
            </div>
        </div>

        <div class="bg-secondary-fixed rounded-xl p-4 mb-6 text-on-secondary-fixed text-sm">
            <p class="flex items-start gap-2">
                <span class="material-symbols-outlined text-base mt-0.5">warning</span>
                <span><strong>Important :</strong> Supprime ce fichier <code>install.php</code> en production pour des raisons de sécurité.</span>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= APP_URL ?>/client/index.php" class="flex-1 bg-primary text-white px-6 py-3 rounded-xl font-medium text-center hover:brightness-110 transition-all shadow-sm">
                <span class="material-symbols-outlined align-middle text-base mr-1">home</span> Voir le site
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="flex-1 bg-surface-container-high px-6 py-3 rounded-xl font-medium text-center hover:bg-surface-container-highest transition-all">
                <span class="material-symbols-outlined align-middle text-base mr-1">login</span> Se connecter
            </a>
        </div>
    </div>
</body>
</html>
