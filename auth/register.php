<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

if (isLogged()) redirect(APP_URL . '/client/index.php');

$errors = [];
$old = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'quartier' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $old['nom']       = trim($_POST['nom'] ?? '');
        $old['prenom']    = trim($_POST['prenom'] ?? '');
        $old['email']     = trim($_POST['email'] ?? '');
        $old['telephone'] = trim($_POST['telephone'] ?? '');
        $old['quartier']  = trim($_POST['quartier'] ?? '');
        $password         = $_POST['password'] ?? '';
        $passwordConfirm  = $_POST['password_confirm'] ?? '';

        if (strlen($old['nom']) < 2) $errors[] = 'Le nom est requis (min 2 caractères).';
        if (strlen($old['prenom']) < 2) $errors[] = 'Le prénom est requis (min 2 caractères).';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (strlen($password) < 6) $errors[] = 'Mot de passe trop court (6 caractères minimum).';
        if ($password !== $passwordConfirm) $errors[] = 'Les mots de passe ne correspondent pas.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (nom, prenom, email, telephone, quartier, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, "client")');
                $stmt->execute([$old['nom'], $old['prenom'], $old['email'], $old['telephone'], $old['quartier'], $hash]);
                $userId = $pdo->lastInsertId();

                session_regenerate_id(true);
                $_SESSION['user_id']     = $userId;
                $_SESSION['user_nom']    = $old['nom'];
                $_SESSION['user_prenom'] = $old['prenom'];
                $_SESSION['user_email']  = $old['email'];
                $_SESSION['user_role']   = 'client';

                setFlash('success', 'Bienvenue chez FoodExpress, ' . $old['prenom'] . ' ! Découvre notre carte.');
                redirect(APP_URL . '/client/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Créer un compte - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary-container to-secondary"></div>
        <div class="absolute top-1/4 -left-32 w-96 h-96 bg-tertiary rounded-full blur-3xl opacity-30"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-secondary-fixed rounded-full blur-3xl opacity-30"></div>
    </div>

    <div class="w-full max-w-5xl grid md:grid-cols-2 bg-white rounded-2xl shadow-2xl overflow-hidden animate-fade-in">
        <div class="p-8 md:p-12">
            <a href="<?= APP_URL ?>/client/index.php" class="flex items-center gap-2 mb-6">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
                    <span class="material-symbols-outlined icon-fill">restaurant</span>
                </div>
                <div>
                    <div class="font-display font-bold">FoodExpress</div>
                    <div class="text-xs text-on-surface-variant -mt-0.5">Dakar · Sénégal</div>
                </div>
            </a>

            <h1 class="font-display text-3xl font-bold mb-2">Rejoins-nous</h1>
            <p class="text-on-surface-variant mb-8">Crée ton compte et savoure la Teranga.</p>

            <?php if (!empty($errors)): ?>
                <div class="bg-error-container text-on-error-container rounded-xl p-4 mb-6 flex items-start gap-3">
                    <span class="material-symbols-outlined">error</span>
                    <div class="text-sm">
                        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <?= csrfField() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Prénom</label>
                        <input type="text" name="prenom" value="<?= e($old['prenom']) ?>" required class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nom</label>
                        <input type="text" name="nom" value="<?= e($old['nom']) ?>" required class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">mail</span>
                        <input type="email" name="email" value="<?= e($old['email']) ?>" required class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Téléphone</label>
                        <input type="tel" name="telephone" value="<?= e($old['telephone']) ?>" placeholder="+221 77 ..." class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Quartier</label>
                        <select name="quartier" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            <option value="">Choisir...</option>
                            <?php foreach (QUARTIERS_DAKAR as $q): ?>
                                <option <?= $q === $old['quartier'] ? 'selected' : '' ?>><?= e($q) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Mot de passe</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">lock</span>
                        <input type="password" name="password" required minlength="6" class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Confirme le mot de passe</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">lock</span>
                        <input type="password" name="password_confirm" required class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-semibold py-3.5 rounded-xl hover:brightness-110 active:scale-[0.98] transition-all shadow-md flex items-center justify-center gap-2 mt-6">
                    Créer mon compte <span class="material-symbols-outlined">arrow_forward</span>
                </button>

                <p class="text-center text-sm text-on-surface-variant pt-2">
                    Déjà inscrit ?
                    <a href="login.php" class="text-primary font-semibold hover:underline">Connecte-toi</a>
                </p>
            </form>
        </div>

        <div class="hidden md:block relative">
            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuA3PUFypMCNaW2lL3HpGHHMddyabIZcx6dEFlrHHNMXLfPFmFYAPFKVnNnwMPCDFlVapGvvDJ2GMbkkRrDhUjB8vFNmiqTqj9yZBvjfFhd7UxvsfJ_-l9hPh70oHPl9VMH1T08RvvN0cl8slrhudGITrqdUjNVppLTnm3_urtZE2zepovq64-FKU7X4g9oX5qJQLlvYdX6QkQaHf8gcyfeiSH5-NdceBe8FskehOgkWP3ewUf5Wl6SdrgWySEnPPiPg9wUcvCEjaiY" class="w-full h-full object-cover" alt="Plat sénégalais">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-10 text-white">
                <h2 class="font-display text-3xl font-bold mb-2 leading-tight">Rejoins la famille FoodExpress.</h2>
                <p class="opacity-90 text-sm">Profite de -20% sur ta première commande avec le code <span class="bg-primary px-2 py-0.5 rounded font-bold">DAKAR20</span></p>
            </div>
        </div>
    </div>
</body>
</html>
