<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$tierOrder = "FIELD(tier, 'gold', 'silver', 'bronze')";
$members = $pdo->query("SELECT * FROM team_members ORDER BY $tierOrder, order_index, full_name")->fetchAll();

$tierLabels = [
    'gold'   => ['🥇 Or — Direction', 'bg-warning text-dark'],
    'silver' => ['🥈 Argent — Responsable', 'bg-secondary'],
    'bronze' => ['🥉 Bronze — Employé', 'bg-brown'],
];

$pageTitle = 'Équipe';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Équipe</h5>
    <a href="<?= BASE_URL ?>/admin/team/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un membre</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Photo</th><th>Nom</th><th>Fonction</th><th>Catégorie</th><th>Ordre</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td>
                    <?php if ($m['photo']): ?>
                        <img src="<?= UPLOAD_URL ?>team/<?= e($m['photo']) ?>" class="thumb-preview">
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= e($m['full_name']) ?></td>
                <td><?= e($m['role']) ?></td>
                <td>
                    <?php $tl = $tierLabels[$m['tier']] ?? $tierLabels['bronze']; ?>
                    <span class="badge <?= $tl[1] ?>"><?= $tl[0] ?></span>
                </td>
                <td><?= (int)$m['order_index'] ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/team/form.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/team/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$members): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun membre pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
