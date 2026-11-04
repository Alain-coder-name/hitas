<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['title' => '', 'source' => '', 'excerpt' => '', 'image' => '', 'external_link' => '', 'published_date' => '', 'is_published' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM press_mentions WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Mention introuvable.'); }
    $item = $found;
}

$pageTitle = ($id ? 'Modifier' : 'Ajouter') . ' une mention presse';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/press/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Titre</label>
            <input type="text" name="title" class="form-control" value="<?= e($item['title']) ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Source <span class="text-muted fw-normal">(nom du média)</span></label>
                <input type="text" name="source" class="form-control" placeholder="Ex : Cameroon Tribune, Jeune Afrique..." value="<?= e($item['source'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Date de publication <span class="text-muted fw-normal">(optionnel)</span></label>
                <input type="date" name="published_date" class="form-control" value="<?= e($item['published_date'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Résumé <span class="text-muted fw-normal">(optionnel)</span></label>
            <textarea name="excerpt" class="form-control" rows="3"><?= e($item['excerpt'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Lien vers l'article original <span class="text-muted fw-normal">(optionnel)</span></label>
            <input type="url" name="external_link" class="form-control" placeholder="https://" value="<?= e($item['external_link'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="is_published" class="form-select">
                <option value="1" <?= $item['is_published'] ? 'selected' : '' ?>>Publié</option>
                <option value="0" <?= !$item['is_published'] ? 'selected' : '' ?>>Brouillon</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <?php if (!empty($item['image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>press/<?= e($item['image']) ?>" class="thumb-preview" style="width:100px;height:100px;"></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Propre à cette rubrique — n'est jamais reprise dans les actualités classiques ni dans les bulles de l'accueil.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/press/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
