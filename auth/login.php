<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Déjà connecté
if (isLogged()) {
    redirect(isAdmin() ? APP_URL . '/admin/index.php' : APP_URL . '/client/index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide. Recharge la page.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (empty($password)) $errors[] = 'Le mot de passe est requis.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['user_nom']    = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_email']  = $user['email'];
                $_SESSION['user_role']   = $user['role'];

                setFlash('success', 'Bienvenue ' . $user['prenom'] . ' ! Bonne dégustation.');

                $redirectAfter = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);
                if ($redirectAfter) redirect($redirectAfter);
                redirect($user['role'] === 'admin' ? APP_URL . '/admin/index.php' : APP_URL . '/client/index.php');
            } else {
                $errors[] = 'Email ou mot de passe incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion - <?= APP_NAME ?></title>
    <?php require __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
    <!-- Background décoratif -->
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary-container to-secondary"></div>
        <div class="absolute top-1/4 -left-32 w-96 h-96 bg-secondary-fixed rounded-full blur-3xl opacity-30"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-tertiary rounded-full blur-3xl opacity-30"></div>
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22%3E%3Cpath d=%22M30 30 L0 0 M30 30 L60 0 M30 30 L0 60 M30 30 L60 60%22 stroke=%22white%22 stroke-width=%220.5%22 fill=%22none%22/%3E%3C/svg%3E');"></div>
    </div>

    <div class="w-full max-w-5xl grid md:grid-cols-2 bg-white rounded-2xl shadow-2xl overflow-hidden animate-fade-in">
        <!-- Côté gauche : image immersive -->
        <div class="hidden md:block relative">
            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAI8zbylCh9DPLk5tJYh5YGf2VnJ14E6T2a0zmM3nuX5SLdzVQXyFMYObbGhnnxA8kqOBsjBFVUt6Vs_Yw4J32iEx_FFPDWnH1k8AKuWcFXwW7I_Tcj1Sp-sE4Do0M3mbi8p7_FE-zeghDKKMxQf1ovFyMjTpHGgcQFKwSZ2UU3MpYD8_5roRy_j5ydPyU7BLgyU8fpjnvH5kGK6NtsbYwvSpby_qZKAi9y6mkPiGZVPOj9V2brWMP0zFvo2a5aTPX2NgwntIOYt8s" class="w-full h-full object-cover" alt="Cuisine sénégalaise">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-10 text-white">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center">
                        <span class="material-symbols-outlined icon-fill">restaurant</span>
                    </div>
                    <div>
                        <div class="font-display font-bold">FoodExpress</div>
                        <div class="text-xs opacity-80 -mt-0.5">Dakar · Sénégal</div>
                    </div>
                </div>
                <h2 class="font-display text-3xl font-bold mb-2 leading-tight">Le goût de la Teranga, livré chez vous.</h2>
                <p class="opacity-90 text-sm">Plats traditionnels sénégalais cuisinés avec amour.</p>
            </div>
        </div>

        <!-- Côté droit : formulaire -->
        <div class="p-8 md:p-12 flex flex-col justify-center">
            <a href="<?= APP_URL ?>/client/index.php" class="md:hidden flex items-center gap-2 mb-6">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
                    <span class="material-symbols-outlined icon-fill">restaurant</span>
                </div>
                <div>
                    <div class="font-display font-bold">FoodExpress</div>
                    <div class="text-xs text-on-surface-variant -mt-0.5">Dakar · Sénégal</div>
                </div>
            </a>

            <h1 class="font-display text-3xl font-bold mb-2">Bon retour !</h1>
            <p class="text-on-surface-variant mb-8">Connecte-toi pour continuer à savourer.</p>

            <?php if (!empty($errors)): ?>
                <div class="bg-error-container text-on-error-container rounded-xl p-4 mb-6 flex items-start gap-3">
                    <span class="material-symbols-outlined">error</span>
                    <div class="text-sm">
                        <?php foreach ($errors as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <?= csrfField() ?>

                <div>
                    <label class="block text-sm font-semibold mb-2">Adresse email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">mail</span>
                        <input type="email" name="email" value="<?= e($email) ?>" required
                            placeholder="exemple@email.com"
                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label class="text-sm font-semibold">Mot de passe</label>
                        <a href="forgot.php" class="text-sm text-primary hover:underline">Mot de passe oublié ?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">lock</span>
                        <input type="password" name="password" id="password" required
                            placeholder="••••••••"
                            class="w-full pl-12 pr-12 py-3.5 rounded-xl border border-outline-variant bg-surface-container-low focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                        <button type="button" onclick="togglePwd()" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary">
                            <span class="material-symbols-outlined" id="eye-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-semibold py-3.5 rounded-xl hover:brightness-110 active:scale-[0.98] transition-all shadow-md flex items-center justify-center gap-2">
                    Se connecter <span class="material-symbols-outlined">arrow_forward</span>
                </button>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-outline-variant"></div></div>
                    <div class="relative flex justify-center text-xs"><span class="bg-white px-3 text-on-surface-variant">ou</span></div>
                </div>

                <p class="text-center text-sm text-on-surface-variant">
                    Pas encore de compte ?
                    <a href="register.php" class="text-primary font-semibold hover:underline">Crée-en un gratuitement</a>
                </p>
            </form>

        </div>
    </div>

    <script>
        function togglePwd() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</body>
</html>
