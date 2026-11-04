<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pdo = getDB();
$key = trim($_GET['key'] ?? '');
$labels = [
    'a-propos'             => 'À propos',
    'mission'               => 'Notre mission',
    'admission-conditions'  => "Conditions d'admission",
];
if (!array_key_exists($key, $labels)) { die('Galerie inconnue.'); }

$stmt = $pdo->prepare('SELECT * FROM page_galleries WHERE page_key = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$key]);
$images = $stmt->fetchAll();

$pageTitle = 'Photos — ' . $labels[$key];
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Photos — <?= e($labels[$key]) ?></h5>
        <p class="text-muted small mb-0">Ces photos défilent en diaporama à cet emplacement sur le site public.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/page_galleries/hub.php" class="btn btn-outline-secondary btn-sm">← Toutes les galeries</a>
        <a href="<?= BASE_URL ?>/admin/page_galleries/form.php?key=<?= urlencode($key) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une photo</a>
    </div>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th>Photo</th><th style="width:120px;"></th></tr></thead>
        <tbody>
        <?php foreach ($images as $i => $img): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/page_galleries/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
                            <input type="hidden" name="key" value="<?= e($key) ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/page_galleries/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
                            <input type="hidden" name="key" value="<?= e($key) ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($images) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td><img src="<?= UPLOAD_URL ?>page_galleries/<?= e($img['image']) ?>" class="thumb-preview"></td>
                <td class="text-end">
                    <form action="<?= BASE_URL ?>/admin/page_galleries/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
                        <input type="hidden" name="key" value="<?= e($key) ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$images): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">Aucune photo pour le moment — le diaporama ne s'affichera qu'une fois au moins une photo ajoutée.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
