<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['question' => '', 'answer' => '', 'order_index' => 0, 'is_published' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM faq WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Question introuvable.'); }
    $item = $found;
}

$pageTitle = $id ? 'Modifier une question' : 'Ajouter une question';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/faq/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Question</label>
            <input type="text" name="question" class="form-control" value="<?= e($item['question']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Réponse</label>
            <textarea name="answer" class="form-control" rows="4" required><?= e($item['answer']) ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Ordre d'affichage</label>
                <input type="number" name="order_index" class="form-control" value="<?= (int)$item['order_index'] ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Statut</label>
                <select name="is_published" class="form-select">
                    <option value="1" <?= $item['is_published'] ? 'selected' : '' ?>>Publié</option>
                    <option value="0" <?= !$item['is_published'] ? 'selected' : '' ?>>Masqué</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/faq/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
