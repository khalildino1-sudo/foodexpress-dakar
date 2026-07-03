<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

// Valeurs par défaut
$plat = [
    'nom' => '', 'categorie_id' => '', 'description' => '', 'ingredients' => '',
    'prix' => '', 'prix_promo' => '', 'temps_preparation' => 30, 'calories' => '',
    'image' => '', 'epice' => 0, 'vedette' => 0, 'disponible' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM plats WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        setFlash('error', 'Plat introuvable.');
        redirect('plats.php');
    }
    $plat = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expirée, réessayez.';
    } else {
        $plat['nom']               = trim($_POST['nom'] ?? '');
        $plat['categorie_id']      = (int)($_POST['categorie_id'] ?? 0);
        $plat['description']       = trim($_POST['description'] ?? '');
        $plat['ingredients']       = trim($_POST['ingredients'] ?? '');
        $plat['prix']              = (float)($_POST['prix'] ?? 0);
        $plat['prix_promo']        = $_POST['prix_promo'] !== '' ? (float)$_POST['prix_promo'] : null;
        $plat['temps_preparation'] = (int)($_POST['temps_preparation'] ?? 30);
        $plat['calories']          = $_POST['calories'] !== '' ? (int)$_POST['calories'] : null;
        $plat['epice']             = isset($_POST['epice']) ? 1 : 0;
        $plat['vedette']           = isset($_POST['vedette']) ? 1 : 0;
        $plat['disponible']        = isset($_POST['disponible']) ? 1 : 0;

        // Validation
        if ($plat['nom'] === '')              $errors[] = 'Le nom est obligatoire.';
        if ($plat['categorie_id'] <= 0)       $errors[] = 'Choisissez une catégorie.';
        if ($plat['prix'] <= 0)               $errors[] = 'Le prix doit être supérieur à 0.';
        if ($plat['prix_promo'] !== null && $plat['prix_promo'] >= $plat['prix']) {
            $errors[] = 'Le prix promo doit être inférieur au prix normal.';
        }

        // Upload image
        $imageName = $plat['image'];
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (!in_array($file['type'], $allowed)) {
                    $errors[] = 'Format d\'image invalide (JPG, PNG, WEBP uniquement).';
                } elseif ($file['size'] > 3 * 1024 * 1024) {
                    $errors[] = 'Image trop lourde (max 3 Mo).';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $imageName = 'plat_' . uniqid() . '.' . strtolower($ext);
                    $dest = __DIR__ . '/../assets/uploads/' . $imageName;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'Échec du téléversement de l\'image.';
                        $imageName = $plat['image'];
                    }
                }
            }
        }

        if (empty($errors)) {
            $slug = slugify($plat['nom']);
            if ($isEdit) {
                $stmt = $pdo->prepare("UPDATE plats SET categorie_id=?, nom=?, slug=?, description=?, ingredients=?, prix=?, prix_promo=?, image=?, temps_preparation=?, calories=?, epice=?, vedette=?, disponible=? WHERE id=?");
                $stmt->execute([
                    $plat['categorie_id'], $plat['nom'], $slug, $plat['description'], $plat['ingredients'],
                    $plat['prix'], $plat['prix_promo'], $imageName, $plat['temps_preparation'],
                    $plat['calories'], $plat['epice'], $plat['vedette'], $plat['disponible'], $id
                ]);
                setFlash('success', 'Plat « ' . $plat['nom'] . ' » mis à jour.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO plats (categorie_id, nom, slug, description, ingredients, prix, prix_promo, image, temps_preparation, calories, epice, vedette, disponible) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $plat['categorie_id'], $plat['nom'], $slug, $plat['description'], $plat['ingredients'],
                    $plat['prix'], $plat['prix_promo'], $imageName, $plat['temps_preparation'],
                    $plat['calories'], $plat['epice'], $plat['vedette'], $plat['disponible']
                ]);
                setFlash('success', 'Plat « ' . $plat['nom'] . ' » ajouté au menu.');
            }
            redirect('plats.php');
        }
    }
}

$pageTitle = $isEdit ? 'Modifier un plat' : 'Nouveau plat';
$activeMenu = 'plats';
include __DIR__ . '/../includes/admin-sidebar.php';
?>

<div class="mb-4">
    <a href="plats.php" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-base">arrow_back</span> Retour aux plats
    </a>
</div>

<?php if ($errors): ?>
    <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl flex items-start gap-3">
        <span class="material-symbols-outlined">error</span>
        <div class="text-sm"><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= csrfField() ?>

    <!-- Colonne principale -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Informations générales</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nom du plat *</label>
                    <input type="text" name="nom" value="<?= e($plat['nom']) ?>" required
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Catégorie *</label>
                    <select name="categorie_id" required class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary">
                        <option value="">— Choisir —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)$plat['categorie_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20"><?= e($plat['description']) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Ingrédients <span class="text-on-surface-variant font-normal">(séparés par des virgules)</span></label>
                    <textarea name="ingredients" rows="2" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20"><?= e($plat['ingredients']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Tarification & détails</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Prix (FCFA) *</label>
                    <input type="number" name="prix" value="<?= e($plat['prix']) ?>" min="0" step="50" required
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Prix promo (FCFA)</label>
                    <input type="number" name="prix_promo" value="<?= e($plat['prix_promo']) ?>" min="0" step="50"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Préparation (min)</label>
                    <input type="number" name="temps_preparation" value="<?= e($plat['temps_preparation']) ?>" min="1"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Calories (kcal)</label>
                    <input type="number" name="calories" value="<?= e($plat['calories']) ?>" min="0"
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne latérale -->
    <div class="space-y-6">
        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Image du plat</h3>
            <div class="aspect-square rounded-xl bg-surface-container overflow-hidden mb-3 flex items-center justify-center" id="imgPreview">
                <?php if ($plat['image']): ?>
                    <img src="../assets/uploads/<?= e($plat['image']) ?>" onerror="this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-5xl text-on-surface-variant\'>image</span>'" class="w-full h-full object-cover" alt="">
                <?php else: ?>
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant">add_photo_alternate</span>
                <?php endif; ?>
            </div>
            <label class="block">
                <input type="file" name="image" accept="image/*" onchange="previewImg(this)" class="hidden">
                <span class="block text-center px-4 py-2.5 bg-primary-fixed text-primary rounded-xl cursor-pointer hover:bg-primary-fixed-dim transition-colors text-sm font-medium">
                    <span class="material-symbols-outlined text-base align-middle">upload</span> Choisir une image
                </span>
            </label>
            <p class="text-xs text-on-surface-variant mt-2 text-center">JPG, PNG ou WEBP · max 3 Mo</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/20">
            <h3 class="font-display font-semibold text-lg mb-4">Options</h3>
            <div class="space-y-3">
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm flex items-center gap-2"><span class="material-symbols-outlined text-secondary">star</span> Plat vedette</span>
                    <input type="checkbox" name="vedette" <?= $plat['vedette'] ? 'checked' : '' ?> class="rounded text-primary focus:ring-primary w-5 h-5">
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm flex items-center gap-2"><span class="material-symbols-outlined text-error">local_fire_department</span> Plat épicé</span>
                    <input type="checkbox" name="epice" <?= $plat['epice'] ? 'checked' : '' ?> class="rounded text-primary focus:ring-primary w-5 h-5">
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm flex items-center gap-2"><span class="material-symbols-outlined text-tertiary">check_circle</span> Disponible</span>
                    <input type="checkbox" name="disponible" <?= $plat['disponible'] ? 'checked' : '' ?> class="rounded text-primary focus:ring-primary w-5 h-5">
                </label>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <button type="submit" class="w-full py-3 bg-primary text-white font-semibold rounded-xl hover:brightness-110 active:scale-[0.98] transition-all shadow-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">save</span>
                <?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter le plat' ?>
            </button>
            <?php if ($isEdit): ?>
                <a href="plat-options.php?plat_id=<?= $id ?>" class="w-full py-3 bg-secondary-fixed text-on-secondary-fixed-variant text-center font-medium rounded-xl hover:brightness-95 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">tune</span> Gérer les options / suppléments
                </a>
            <?php endif; ?>
            <a href="plats.php" class="w-full py-3 bg-surface-container text-center font-medium rounded-xl hover:bg-surface-container-high transition-colors">Annuler</a>
        </div>
    </div>
</form>

<script>
    function previewImg(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('imgPreview').innerHTML =
                    '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
