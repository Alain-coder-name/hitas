<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$blockId = (int)($_GET['block_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM prep_blocks WHERE id = ?');
$stmt->execute([$blockId]);
$block = $stmt->fetch();
if (!$block) { die('Bloc de mise à niveau introuvable.'); }

$stmt = $pdo->prepare('SELECT * FROM prep_semesters WHERE prep_block_id = ? ORDER BY sort_order ASC, semester_number ASC');
$stmt->execute([$blockId]);
$semesters = $stmt->fetchAll();

$pageTitle = 'Semestres — ' . $block['title'];
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Semestres du bloc « <?= e($block['title']) ?> »</h5>
        <p class="text-muted small mb-0">Détaille combien de semestres compose ce parcours de mise à niveau, leur période, et les matières enseignées à chacun. C'est ce détail qui s'affiche sur la page publique Programmes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/prep_blocks/list.php" class="btn btn-outline-secondary btn-sm">← Retour aux blocs</a>
        <a href="<?= BASE_URL ?>/admin/prep_semesters/form.php?block_id=<?= $blockId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un semestre</a>
    </div>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th style="width:90px;">Ordre</th><th style="width:110px;">Semestre</th><th>Période</th><th>Matières enseignées</th><th style="width:130px;"></th></tr></thead>
        <tbody>
        <?php foreach ($semesters as $i => $s): ?>
            <tr>
                <td>
                    <div class="d-flex gap-1">
                        <form action="<?= BASE_URL ?>/admin/prep_semesters/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="block_id" value="<?= $blockId ?>">
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Monter"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>/admin/prep_semesters/move.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="block_id" value="<?= $blockId ?>">
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn-sm btn-outline-secondary" <?= $i === count($semesters) - 1 ? 'disabled' : '' ?> title="Descendre"><i class="bi bi-arrow-down"></i></button>
                        </form>
                    </div>
                </td>
                <td class="fw-semibold">Semestre <?= (int)$s['semester_number'] ?></td>
                <td><?= e($s['period_label']) ?></td>
                <td class="small text-muted">
                    <?php
                    $subjectsList = array_filter(array_map('trim', explode("\n", (string)$s['subjects'])));
                    echo $subjectsList ? e(implode(', ', $subjectsList)) : '<span class="fst-italic">Aucune matière renseignée</span>';
                    ?>
                </td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/prep_semesters/form.php?block_id=<?= $blockId ?>&id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="<?= BASE_URL ?>/admin/prep_semesters/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <input type="hidden" name="block_id" value="<?= $blockId ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$semesters): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun semestre pour le moment. Ajoutez le premier pour détailler ce parcours.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
