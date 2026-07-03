<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

$message = null;
$type = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $type = 'error'; $message = 'Token invalide.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $type = 'error'; $message = 'Email invalide.';
        } else {
            $stmt = $pdo->prepare('SELECT id, prenom FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
                $stmt->execute([$token, $expires, $user['id']]);
                @sendPasswordReset($email, $user['prenom'], $token);
            }
            // Réponse uniforme pour éviter l'énumération d'emails
            $type = 'success';
            $message = 'Si cette adresse existe dans notre système, un email de réinitialisation a été envoyé.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mot de passe oublié - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-primary via-primary-container to-secondary"></div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 md:p-10 animate-fade-in">
        <a href="<?= APP_URL ?>/client/index.php" class="flex items-center gap-2 mb-6">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
                <span class="material-symbols-outlined icon-fill">restaurant</span>
            </div>
            <div>
                <div class="font-display font-bold">FoodExpress</div>
                <div class="text-xs text-on-surface-variant -mt-0.5">Dakar · Sénégal</div>
            </div>
        </a>

        <div class="w-16 h-16 rounded-xl bg-primary-fixed flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-primary text-3xl">lock_reset</span>
        </div>

        <h1 class="font-display text-2xl font-bold mb-2">Mot de passe oublié ?</h1>
        <p class="text-on-surface-variant mb-6 text-sm">Pas de souci. Entre ton email et nous t'enverrons un lien de réinitialisation.</p>

        <?php if ($message): ?>
            <div class="<?= $type === 'success' ? 'bg-tertiary text-white' : 'bg-error-container text-on-error-container' ?> rounded-xl p-4 mb-6 flex items-start gap-3">
                <span class="material-symbols-outlined"><?= $type === 'success' ? 'check_circle' : 'error' ?></span>
                <span class="text-sm"><?= e($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <?= csrfField() ?>
            <div>
                <label class="block text-sm font-semibold mb-2">Adresse email</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">mail</span>
                    <input type="email" name="email" required class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>
            </div>
            <button type="submit" class="w-full bg-primary text-white font-semibold py-3.5 rounded-xl hover:brightness-110 active:scale-[0.98] transition-all shadow-md">
                Envoyer le lien
            </button>
            <p class="text-center text-sm">
                <a href="login.php" class="text-primary hover:underline flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-base">arrow_back</span> Retour à la connexion
                </a>
            </p>
        </form>
    </div>
</body>
</html>
