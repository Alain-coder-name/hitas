<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$items = $pdo->query('SELECT * FROM nav_menu ORDER BY order_index, id')->fetchAll();

$pageTitle = 'Navigation';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Menu du site public</h5>
    <a href="<?= BASE_URL ?>/admin/navigation/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un lien</a>
</div>

<div class="alert alert-info small">
    Ce menu gère les liens simples de la barre de navigation (Accueil, À propos, Programmes...).
    Le bouton « Postulez » et le sélecteur de campus restent des éléments fonctionnels fixes, gérés séparément.
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th>Libellé</th><th>Lien</th><th>Visibilité</th><th style="width:160px;"></th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $n): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/navigation/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/navigation/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($items) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td><?= e($n['label']) ?></td>
                <td class="text-muted small"><?= e($n['url']) ?></td>
                <td>
                    <form action="<?= BASE_URL ?>/admin/navigation/save.php" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <input type="hidden" name="label" value="<?= e($n['label']) ?>">
                        <input type="hidden" name="url" value="<?= e($n['url']) ?>">
                        <input type="hidden" name="is_visible" value="<?= $n['is_visible'] ? 0 : 1 ?>">
                        <button class="btn btn-sm <?= $n['is_visible'] ? 'btn-outline-success' : 'btn-outline-secondary' ?>">
                            <?= $n['is_visible'] ? '<i class="bi bi-eye"></i> Visible' : '<i class="bi bi-eye-slash"></i> Masqué' ?>
                        </button>
                    </form>
                </td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/navigation/form.php?id=<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/navigation/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun lien pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
