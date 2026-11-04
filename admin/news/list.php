<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$news = $pdo->query('SELECT * FROM news ORDER BY published_at DESC')->fetchAll();

$pageTitle = 'Actualités';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Actualités</h5>
    <a href="<?= BASE_URL ?>/admin/news/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un article</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Image</th><th>Titre</th><th>Type</th><th>Publié le</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($news as $n): ?>
            <tr>
                <td>
                    <?php if ($n['image']): ?>
                        <img src="<?= UPLOAD_URL ?>news/<?= e($n['image']) ?>" class="thumb-preview">
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= e($n['title']) ?></td>
                <td>
                    <?= $n['is_advertisement'] ? '<span class="badge bg-warning text-dark">Publicité</span>' : '<span class="badge bg-info text-dark">Actualité</span>' ?>
                    <?php if ($n['is_advertisement']): ?>
                        <?php if (!empty($n['external_link'])): ?>
                            <div class="small text-success mt-1"><i class="bi bi-link-45deg"></i> lien OK</div>
                        <?php else: ?>
                            <div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle"></i> lien manquant</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><?= e($n['published_at']) ?></td>
                <td><?= $n['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Brouillon</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/news/form.php?id=<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/news/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$news): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun article pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
