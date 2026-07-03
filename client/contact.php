<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($nom) || empty($email) || empty($message)) $errors[] = 'Tous les champs marqués * sont requis.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

        if (empty($errors)) {
            $html = '<h2>Nouveau message de contact</h2>
                    <p><strong>De :</strong> ' . e($nom) . ' &lt;' . e($email) . '&gt;</p>
                    <p><strong>Sujet :</strong> ' . e($sujet) . '</p>
                    <p><strong>Message :</strong></p>
                    <p>' . nl2br(e($message)) . '</p>';
            @sendMail(APP_EMAIL, APP_NAME, '[Contact] ' . $sujet, $html);
            setFlash('success', 'Merci ! Votre message a bien été envoyé, nous reviendrons vers vous sous 24h.');
            redirect('contact.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Contact · <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="relative bg-gradient-to-br from-primary via-primary-container to-secondary text-white py-16 overflow-hidden">
        <div class="absolute -top-20 -right-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-4 md:px-8">
            <span class="inline-block px-4 py-1.5 bg-white/15 backdrop-blur rounded-full text-sm font-semibold mb-4">Nous contacter</span>
            <h1 class="font-display text-4xl md:text-5xl font-bold">Une question ? Une suggestion ?</h1>
            <p class="opacity-90 mt-3 max-w-xl">Notre équipe est là pour vous, du lundi au dimanche, de 9h à 23h.</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 md:px-8 py-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white rounded-2xl p-8 border border-outline-variant/30">
            <h2 class="font-display text-2xl font-bold mb-6">Envoyez-nous un message</h2>

            <?php if (!empty($errors)): ?>
                <div class="bg-error-container text-on-error-container px-4 py-3 rounded-xl mb-6">
                    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <?= csrfField() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nom complet *</label>
                        <input type="text" name="nom" required value="<?= e($_POST['nom'] ?? ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?>"
                               class="w-full px-4 py-3 rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Email *</label>
                        <input type="email" name="email" required value="<?= e($_POST['email'] ?? $_SESSION['user_email'] ?? '') ?>"
                               class="w-full px-4 py-3 rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Sujet</label>
                    <input type="text" name="sujet" value="<?= e($_POST['sujet'] ?? '') ?>"
                           placeholder="Une question sur ma commande, suggestion..."
                           class="w-full px-4 py-3 rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Message *</label>
                    <textarea name="message" required rows="6"
                              class="w-full px-4 py-3 rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 bg-surface-container-low"><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:brightness-110 active:scale-[0.98] transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined">send</span>
                    Envoyer le message
                </button>
            </form>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl p-6 border border-outline-variant/30">
                <div class="w-12 h-12 rounded-xl bg-primary-fixed text-primary flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined">phone</span>
                </div>
                <h3 class="font-display font-semibold mb-1">Téléphone</h3>
                <a href="tel:+221338000000" class="text-on-surface-variant hover:text-primary"><?= APP_PHONE ?></a>
                <p class="text-xs text-on-surface-variant mt-1">7j/7 · 9h - 23h</p>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-outline-variant/30">
                <div class="w-12 h-12 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined">mail</span>
                </div>
                <h3 class="font-display font-semibold mb-1">Email</h3>
                <a href="mailto:<?= APP_EMAIL ?>" class="text-on-surface-variant hover:text-primary"><?= APP_EMAIL ?></a>
                <p class="text-xs text-on-surface-variant mt-1">Réponse sous 24h</p>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-outline-variant/30">
                <div class="w-12 h-12 rounded-xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined">location_on</span>
                </div>
                <h3 class="font-display font-semibold mb-1">Adresse</h3>
                <p class="text-on-surface-variant text-sm">Avenue Bourguiba<br>Plateau, Dakar · Sénégal</p>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
