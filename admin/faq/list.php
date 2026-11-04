<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$items = $pdo->query('SELECT * FROM faq ORDER BY order_index')->fetchAll();

$pageTitle = 'FAQ';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Questions fréquentes</h5>
    <a href="<?= BASE_URL ?>/admin/faq/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une question</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Question</th><th>Ordre</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($items as $f): ?>
            <tr>
                <td><?= e($f['question']) ?></td>
                <td><?= (int)$f['order_index'] ?></td>
                <td><?= $f['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Masqué</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/faq/form.php?id=<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/faq/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Aucune question pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
