<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$block = ['title' => '', 'description' => '', 'is_published' => 1];
$linkedProgramIds = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM prep_blocks WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Bloc introuvable.'); }
    $block = $found;

    $stmt2 = $pdo->prepare('SELECT program_id FROM prep_block_programs WHERE prep_block_id = ?');
    $stmt2->execute([$id]);
    $linkedProgramIds = array_column($stmt2->fetchAll(), 'program_id');
}

$allPrograms = $pdo->query('SELECT id, title FROM programs ORDER BY title')->fetchAll();

$pageTitle = $id ? 'Modifier un bloc de mise à niveau' : 'Ajouter un bloc de mise à niveau';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <form action="<?= BASE_URL ?>/admin/prep_blocks/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Titre du bloc</label>
            <input type="text" name="title" class="form-control" placeholder="Ex : Ingénierie & Technologie" value="<?= e($block['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description courte (optionnelle)</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Ex : Mise à niveau en maths, physique et anglais technique."><?= e($block['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="is_published" class="form-select" style="max-width:220px;">
                <option value="1" <?= $block['is_published'] ? 'selected' : '' ?>>Publié</option>
                <option value="0" <?= !$block['is_published'] ? 'selected' : '' ?>>Masqué</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Filières incluses dans ce bloc</label>
            <div class="border rounded p-3" style="max-height: 260px; overflow-y:auto;">
                <?php if (!$allPrograms): ?>
                    <p class="text-muted mb-0">Aucune filière enregistrée pour le moment.</p>
                <?php endif; ?>
                <?php foreach ($allPrograms as $program): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="program_ids[]"
                               value="<?= (int)$program['id'] ?>" id="program_<?= (int)$program['id'] ?>"
                               <?= in_array($program['id'], $linkedProgramIds) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="program_<?= (int)$program['id'] ?>">
                            <?= e($program['title']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/prep_blocks/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>

    <?php if ($id): ?>
        <hr class="my-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">Détail semestre par semestre</h6>
                <p class="text-muted small mb-0">Combien de semestres, leur période, les matières enseignées à chacun — c'est ce qui s'affiche en détail sur la page publique.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/prep_semesters/list.php?block_id=<?= (int)$id ?>" class="btn btn-outline-primary btn-sm">Gérer les semestres →</a>
        </div>
    <?php else: ?>
        <hr class="my-4">
        <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Enregistrez d'abord ce bloc — vous pourrez ensuite y détailler les semestres (période, matières).</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
