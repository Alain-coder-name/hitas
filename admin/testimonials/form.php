<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['full_name' => '', 'destination_country' => '', 'program' => '', 'content' => '', 'photo' => '', 'video' => '', 'is_published' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Avis introuvable.'); }
    $item = $found;
}

$pageTitle = $id ? 'Modifier un avis client' : 'Ajouter un avis client';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/testimonials/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nom complet</label>
            <input type="text" name="full_name" class="form-control" value="<?= e($item['full_name']) ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Pays de destination</label>
                <input type="text" name="destination_country" class="form-control" value="<?= e($item['destination_country']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Programme suivi</label>
                <input type="text" name="program" class="form-control" value="<?= e($item['program']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Avis client</label>
            <textarea name="content" class="form-control" rows="5" required><?= e($item['content']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="is_published" class="form-select">
                <option value="1" <?= $item['is_published'] ? 'selected' : '' ?>>Publié</option>
                <option value="0" <?= !$item['is_published'] ? 'selected' : '' ?>>Masqué</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Photo <?= !empty($item['photo']) ? '(laissez vide pour garder l\'actuelle)' : '' ?></label>
            <?php if (!empty($item['photo'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>testimonials/<?= e($item['photo']) ?>" class="thumb-preview" style="width:100px;height:100px;"></div>
            <?php endif; ?>
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="mb-4">
            <label class="form-label">Vidéo <?= !empty($item['video']) ? '(laissez vide pour garder l\'actuelle)' : '(facultatif — remplace la photo si fournie)' ?></label>
            <?php if (!empty($item['video'])): ?>
                <div class="mb-2"><video src="<?= UPLOAD_URL ?>testimonials/<?= e($item['video']) ?>" style="max-height:120px; border-radius:8px;" controls muted></video></div>
            <?php endif; ?>
            <input type="file" name="video" class="form-control" accept=".mp4,.webm">
            <div class="form-text">Formats acceptés : mp4, webm. Un court témoignage filmé est bien plus convaincant qu'une simple photo.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/testimonials/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
