<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$defs = pageBackgroundDefs();

$pageTitle = 'Arrière-plans des pages';
include __DIR__ . '/../header.php';
?>

<p class="text-muted mb-4">
    Gérez ici l'image d'arrière-plan décorative de chaque page, y compris l'Accueil. Par défaut,
    toutes les pages partagent le même filigrane du logo HITAS ; vous pouvez le remplacer par une
    image personnalisée pour une page donnée, la supprimer, ou revenir à ce filigrane par défaut
    à tout moment.
    <br>Si aucune image n'est disponible, la page continue de fonctionner normalement grâce à un
    arrière-plan de secours automatique — rien n'est jamais cassé.
</p>

<div class="row g-4">
    <?php foreach ($defs as $key => $def): $info = getPageBackgroundInfo($key); ?>
        <div class="col-md-6 col-xl-4">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0"><?= e($def['label']) ?></h6>
                    <?php if ($info['source'] === 'custom'): ?>
                        <span class="badge bg-primary">Image personnalisée</span>
                    <?php elseif ($info['source'] === 'default'): ?>
                        <span class="badge bg-secondary">Image par défaut</span>
                    <?php else: ?>
                        <span class="badge bg-light text-dark border">Aucune image (secours CSS)</span>
                    <?php endif; ?>
                </div>

                <?php if ($info['url']): ?>
                    <img src="<?= e($info['url']) ?>" class="w-100 rounded mb-3" style="height:150px;object-fit:cover;" alt="Aperçu — <?= e($def['label']) ?>">
                <?php else: ?>
                    <div class="w-100 rounded mb-3 d-flex align-items-center justify-content-center text-muted small" style="height:150px;background:#f1f3f5;border:1px dashed #ced4da;">
                        Aucun aperçu — arrière-plan de secours utilisé
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/admin/page_backgrounds/save.php" method="post" enctype="multipart/form-data" class="mb-2" data-preview-form>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="page_key" value="<?= e($key) ?>">
                    <div class="input-group input-group-sm">
                        <input type="file" name="background_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required data-preview-input>
                        <button type="submit" class="btn btn-outline-primary">
                            <?= $info['source'] === 'custom' ? 'Remplacer' : 'Téléverser' ?>
                        </button>
                    </div>
                    <div class="form-text">JPG, JPEG, PNG ou WEBP — 5 Mo maximum.</div>
                    <img data-preview-img class="w-100 rounded mt-2 d-none" style="height:150px;object-fit:cover;" alt="">
                </form>

                <div class="d-flex gap-2">
                    <form action="<?= BASE_URL ?>/admin/page_backgrounds/delete.php" method="post" class="js-confirm-delete flex-fill">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="page_key" value="<?= e($key) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" <?= $info['source'] === 'none' ? 'disabled' : '' ?>>
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                    <form action="<?= BASE_URL ?>/admin/page_backgrounds/restore.php" method="post" class="flex-fill">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="page_key" value="<?= e($key) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100" <?= $info['source'] === 'default' ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-counterclockwise"></i> Défaut
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Prévisualisation avant validation (facultatif, purement côté navigateur).
document.querySelectorAll('[data-preview-form]').forEach(function (form) {
    var input = form.querySelector('[data-preview-input]');
    var img = form.querySelector('[data-preview-img]');
    input.addEventListener('change', function () {
        if (input.files && input.files[0]) {
            img.src = URL.createObjectURL(input.files[0]);
            img.classList.remove('d-none');
        }
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>
