<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$items = $pdo->query('SELECT * FROM testimonials ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Avis clients';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Avis clients</h5>
    <a href="<?= BASE_URL ?>/admin/testimonials/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un avis</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Photo</th><th>Nom</th><th>Destination</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($items as $t): ?>
            <tr>
                <td>
                    <?php if ($t['photo']): ?>
                        <img src="<?= UPLOAD_URL ?>testimonials/<?= e($t['photo']) ?>" class="thumb-preview">
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    <?php if (!empty($t['video'])): ?><span class="badge bg-info text-dark ms-1"><i class="bi bi-camera-reels"></i> Vidéo</span><?php endif; ?>
                </td>
                <td><?= e($t['full_name']) ?></td>
                <td><?= e($t['destination_country']) ?></td>
                <td><?= $t['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-warning text-dark">En attente</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/testimonials/form.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/testimonials/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun avis client pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
