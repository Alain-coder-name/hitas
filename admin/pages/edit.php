<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ?');
$stmt->execute([$id]);
$page = $stmt->fetch();
if (!$page) { die('Page introuvable.'); }

$pageTitle = 'Modifier : ' . $page['title'];
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <form action="<?= BASE_URL ?>/admin/pages/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">

        <div class="mb-3">
            <label class="form-label">Titre de la page</label>
            <input type="text" name="title" class="form-control" value="<?= e($page['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contenu</label>
            <textarea name="content" class="form-control" rows="12"><?= e($page['content']) ?></textarea>
            <div class="form-text">Le HTML simple est autorisé (paragraphes, listes, titres).</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Image de la page</label>
            <?php if (!empty($page['image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>pages/<?= e($page['image']) ?>" class="thumb-preview" style="max-width:180px; max-height:180px; object-fit:cover;"></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Image optionnelle affichée sur la page publique correspondante (À propos, Mission...), si le modèle de page le prévoit.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/pages/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
