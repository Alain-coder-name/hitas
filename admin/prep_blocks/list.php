<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$blocks = $pdo->query('SELECT * FROM prep_blocks ORDER BY sort_order ASC, id ASC')->fetchAll();

// Filières liées à chaque bloc, pour l'aperçu dans la liste
$programsByBlock = [];
$semesterCountByBlock = [];
if ($blocks) {
    $stmt = $pdo->query('SELECT pbp.prep_block_id, p.title
                          FROM prep_block_programs pbp
                          JOIN programs p ON p.id = pbp.program_id
                          ORDER BY p.title');
    foreach ($stmt->fetchAll() as $row) {
        $programsByBlock[$row['prep_block_id']][] = $row['title'];
    }

    $stmt = $pdo->query('SELECT prep_block_id, COUNT(*) AS cnt FROM prep_semesters GROUP BY prep_block_id');
    $semesterCountByBlock = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$pageTitle = 'Blocs de mise à niveau';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Blocs de mise à niveau (page Programmes)</h5>
        <p class="text-muted small mb-0">Regroupez plusieurs filières partageant le même parcours préparatoire HITAS, avec sa durée. Affiché en haut de la page publique <em>Programmes</em>, avant les fiches détaillées.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/prep_blocks/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un bloc</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th>Bloc</th><th>Filières incluses</th><th>Semestres</th><th>Statut</th><th style="width:170px;"></th></tr></thead>
        <tbody>
        <?php foreach ($blocks as $i => $b): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/prep_blocks/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/prep_blocks/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($blocks) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td class="fw-semibold"><?= e($b['title']) ?></td>
                <td class="small text-muted">
                    <?php if (!empty($programsByBlock[$b['id']])): ?>
                        <?= e(implode(', ', $programsByBlock[$b['id']])) ?>
                    <?php else: ?>
                        <span class="fst-italic">Aucune filière associée</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $cnt = (int)($semesterCountByBlock[$b['id']] ?? 0); ?>
                    <?= $cnt > 0
                        ? '<span class="badge bg-primary">' . $cnt . ' semestre' . ($cnt > 1 ? 's' : '') . '</span>'
                        : '<span class="text-danger small">Aucun renseigné</span>' ?>
                </td>
                <td><?= $b['is_published'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Masqué</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/prep_semesters/list.php?block_id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-calendar3"></i> Semestres</a>
                    <a href="<?= BASE_URL ?>/admin/prep_blocks/form.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/prep_blocks/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$blocks): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun bloc de mise à niveau pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
