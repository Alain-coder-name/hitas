<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$stats = $pdo->query('SELECT * FROM stats ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Chiffres clés';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Chiffres clés — bande défilante de l'accueil</h5>
        <p class="text-muted small mb-0">Affichés en boucle dans la bande défilante juste sous le bandeau principal de l'accueil. Libellé, valeur et icône entièrement libres. Si la liste est vide, le site affiche automatiquement les 3 compteurs réels (programmes / partenaires / pays).</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/stats/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un chiffre</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th style="width:50px;"></th><th>Valeur</th><th>Libellé</th><th>Visibilité</th><th style="width:130px;"></th></tr></thead>
        <tbody>
        <?php foreach ($stats as $i => $s): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/stats/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/stats/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($stats) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td><?php if ($s['icon']): ?><i class="bi <?= e($s['icon']) ?> fs-5"></i><?php endif; ?></td>
                <td class="fw-semibold"><?= e($s['value']) ?></td>
                <td><?= e($s['label']) ?></td>
                <td>
                    <form action="<?= BASE_URL ?>/admin/stats/save.php" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <input type="hidden" name="label" value="<?= e($s['label']) ?>">
                        <input type="hidden" name="value" value="<?= e($s['value']) ?>">
                        <input type="hidden" name="icon" value="<?= e($s['icon'] ?? '') ?>">
                        <input type="hidden" name="is_active" value="<?= $s['is_active'] ? 0 : 1 ?>">
                        <button class="btn btn-sm <?= $s['is_active'] ? 'btn-outline-success' : 'btn-outline-secondary' ?>">
                            <?= $s['is_active'] ? '<i class="bi bi-eye"></i> Visible' : '<i class="bi bi-eye-slash"></i> Masqué' ?>
                        </button>
                    </form>
                </td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/stats/form.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/stats/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$stats): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun chiffre clé pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
