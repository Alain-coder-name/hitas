<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$key = trim($_GET['key'] ?? '');
$labels = [
    'a-propos'             => 'À propos',
    'mission'               => 'Notre mission',
    'admission-conditions'  => "Conditions d'admission",
];
if (!array_key_exists($key, $labels)) { die('Galerie inconnue.'); }

$pageTitle = 'Ajouter une photo — ' . $labels[$key];
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 600px;">
    <h5 class="mb-3"><?= e($labels[$key]) ?></h5>
    <form action="<?= BASE_URL ?>/admin/page_galleries/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="key" value="<?= e($key) ?>">

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="<?= BASE_URL ?>/admin/page_galleries/list.php?key=<?= urlencode($key) ?>" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
