<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$partners = $pdo->query('SELECT * FROM partners ORDER BY sort_order ASC, id ASC')->fetchAll();
$programCounts = $pdo->query('SELECT partner_id, COUNT(*) AS cnt FROM program_partners GROUP BY partner_id')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Institutions partenaires';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Institutions / établissements partenaires</h5>
        <p class="text-muted small mb-0">Utilisez les flèches pour définir l'ordre d'affichage sur la page publique Partenaires.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/partners/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un partenaire</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th>Logo</th><th>Nom</th><th>Pays</th><th>Filières</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($partners as $i => $p): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/partners/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/partners/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($partners) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td>
                    <?php if ($p['logo']): ?>
                        <img src="<?= UPLOAD_URL ?>partners/<?= e($p['logo']) ?>" class="thumb-preview">
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['country']) ?> <?= !empty($p['flag_emoji']) ? '<span class="flag-emoji">' . e($p['flag_emoji']) . '</span>' : '' ?></td>
                <td>
                    <?php $cnt = (int)($programCounts[$p['id']] ?? 0); ?>
                    <?= $cnt > 0
                        ? '<span class="badge bg-primary">' . $cnt . ' filière' . ($cnt > 1 ? 's' : '') . '</span>'
                        : '<span class="text-danger small">Aucune</span>' ?>
                </td>
                <td><?= $p['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Brouillon</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/partners/form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/partners/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$partners): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucun partenaire pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
