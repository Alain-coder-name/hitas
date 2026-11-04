<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$blockId = (int)($_GET['block_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM prep_blocks WHERE id = ?');
$stmt->execute([$blockId]);
$block = $stmt->fetch();
if (!$block) { die('Bloc de mise à niveau introuvable.'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$semester = ['semester_number' => 1, 'period_label' => '', 'subjects' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM prep_semesters WHERE id = ? AND prep_block_id = ?');
    $stmt->execute([$id, $blockId]);
    $found = $stmt->fetch();
    if (!$found) { die('Semestre introuvable.'); }
    $semester = $found;
} else {
    // Pré-remplit le numéro de semestre suivant, par confort.
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(semester_number), 0) + 1 FROM prep_semesters WHERE prep_block_id = ?');
    $stmt->execute([$blockId]);
    $semester['semester_number'] = (int)$stmt->fetchColumn();
}

$pageTitle = ($id ? 'Modifier' : 'Ajouter') . ' un semestre — ' . $block['title'];
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <h5 class="mb-3">Bloc : <?= e($block['title']) ?></h5>
    <form action="<?= BASE_URL ?>/admin/prep_semesters/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="block_id" value="<?= $blockId ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Numéro du semestre</label>
                <input type="number" name="semester_number" class="form-control" min="1" value="<?= (int)$semester['semester_number'] ?>" required>
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">Période</label>
                <input type="text" name="period_label" class="form-control" placeholder="Ex : Septembre à Décembre 2026" value="<?= e($semester['period_label']) ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Matières enseignées pendant ce semestre</label>
            <textarea name="subjects" class="form-control" rows="8" placeholder="Mathématiques&#10;Physique&#10;Anglais technique&#10;Informatique générale"><?= e($semester['subjects']) ?></textarea>
            <div class="form-text">Une matière par ligne — affichée sous forme de liste sur la page publique Programmes.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/prep_semesters/list.php?block_id=<?= $blockId ?>" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
