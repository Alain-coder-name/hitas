<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$items = $pdo->query('SELECT * FROM press_mentions ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = "HITAS dans l'actualité internationale";
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">HITAS dans l'actualité internationale</h5>
        <p class="text-muted small mb-0">Affichée en bas de la page publique Actualités, dans sa propre rubrique — n'apparaît jamais dans la liste des actualités/publicités, ni dans les bulles de l'accueil.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/press/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une mention</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th>Photo</th><th>Titre</th><th>Source</th><th>Date</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/press/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/press/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($items) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td>
                    <?php if ($item['image']): ?>
                        <img src="<?= UPLOAD_URL ?>press/<?= e($item['image']) ?>" class="thumb-preview">
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= e($item['title']) ?></td>
                <td><?= e($item['source'] ?? '') ?></td>
                <td><?= $item['published_date'] ? e(date('d/m/Y', strtotime($item['published_date']))) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $item['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Brouillon</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/press/form.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/press/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucune mention presse pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
