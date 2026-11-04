<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order ASC, created_at ASC')->fetchAll();

$pageTitle = 'Diaporama accueil';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Diaporama de la page d'accueil</h5>
        <p class="text-muted small mb-0">Ces slides défilent automatiquement juste sous le menu, sur la page d'accueil. Chacune peut avoir sa propre image ou vidéo, son texte, son bouton et sa durée.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/hero_slides/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une slide</a>
</div>

<div class="row g-3">
    <?php foreach ($slides as $slide): ?>
        <div class="col-6 col-md-3">
            <div class="card p-2 gallery-admin-card">
                <?php if (!empty($slide['video'])): ?>
                    <video src="<?= UPLOAD_URL ?>hero/<?= e($slide['video']) ?>" class="w-100 rounded mb-2" style="height:140px;object-fit:cover;" muted></video>
                <?php elseif (!empty($slide['image'])): ?>
                    <img src="<?= UPLOAD_URL ?>hero/<?= e($slide['image']) ?>" class="w-100 rounded mb-2" style="height:140px;object-fit:cover;">
                <?php else: ?>
                    <div class="w-100 rounded mb-2 bg-light d-flex align-items-center justify-content-center text-muted" style="height:140px;"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <div class="small fw-semibold"><?= e($slide['caption'] ?: ($slide['title'] ?: 'Sans légende')) ?></div>
                <div class="small text-muted mb-2">
                    Ordre : <?= (int)$slide['sort_order'] ?> · <?= number_format((int)$slide['duration_ms'] / 1000, 1) ?>s
                    <br>
                    <?= $slide['is_published'] ? '<span class="text-success">Publiée</span>' : '<span class="text-muted">Masquée</span>' ?>
                    <?= !empty($slide['video']) ? ' · <i class="bi bi-camera-video"></i> vidéo' : '' ?>
                    <?= (!empty($slide['title']) || !empty($slide['button_text'])) ? ' · <i class="bi bi-textarea-t"></i> texte propre' : '' ?>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <a href="<?= BASE_URL ?>/admin/hero_slides/form.php?id=<?= (int)$slide['id'] ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-pencil"></i> Modifier</a>
                </div>
                <form action="<?= BASE_URL ?>/admin/hero_slides/delete.php" method="post" class="js-confirm-delete">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Supprimer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$slides): ?>
        <p class="text-muted">Aucune slide pour le moment. Le bloc d'accueil affichera le dégradé par défaut tant qu'aucune slide n'est ajoutée.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
