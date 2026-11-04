<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$tables = [
    'page'    => ['table' => 'pages', 'label' => 'Page', 'slugEditable' => false, 'uploadFolder' => 'pages'],
    'program' => ['table' => 'programs', 'label' => 'Programme', 'slugEditable' => true, 'uploadFolder' => 'programs'],
    'news'    => ['table' => 'news', 'label' => 'Actualité', 'slugEditable' => true, 'uploadFolder' => 'news'],
];

if (!isset($tables[$type])) {
    die('Type invalide.');
}
$conf = $tables[$type];

$stmt = $pdo->prepare("SELECT * FROM {$conf['table']} WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { die('Élément introuvable.'); }

$pageTitle = 'SEO — ' . $row['title'];
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 720px;">
    <h6 class="mb-3"><?= e($conf['label']) ?> : <?= e($row['title']) ?></h6>
    <form action="<?= BASE_URL ?>/admin/seo/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="mb-3">
            <label class="form-label">Slug (URL)</label>
            <?php if ($conf['slugEditable']): ?>
                <input type="text" name="slug" class="form-control" value="<?= e($row['slug']) ?>" required>
                <div class="form-text">Modifier le slug change l'URL publique. Les anciens liens ne redirigeront plus.</div>
            <?php else: ?>
                <input type="text" class="form-control" value="<?= e($row['slug']) ?>" disabled>
                <div class="form-text">Le slug de cette page est une référence technique interne, non modifiable ici.</div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Titre SEO (balise &lt;title&gt;)</label>
            <input type="text" name="meta_title" class="form-control" maxlength="200" value="<?= e($row['meta_title'] ?? '') ?>" placeholder="Laisser vide pour utiliser le titre par défaut">
        </div>

        <div class="mb-3">
            <label class="form-label">Description SEO</label>
            <textarea name="meta_description" class="form-control" rows="3" maxlength="300" placeholder="Laisser vide pour utiliser la description par défaut"><?= e($row['meta_description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Image de partage (Open Graph)</label>
            <?php if (!empty($row['og_image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL . $conf['uploadFolder'] ?>/<?= e($row['og_image']) ?>" class="thumb-preview" style="max-width:220px; max-height:140px; object-fit:cover;"></div>
            <?php endif; ?>
            <input type="file" name="og_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Image affichée lors du partage sur Facebook/LinkedIn/WhatsApp. Laisser vide pour garder le logo par défaut.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/seo/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
