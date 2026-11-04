<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$link = ['label' => '', 'url' => '', 'is_visible' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM nav_menu WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Lien introuvable.'); }
    $link = $found;
}

$pageTitle = $id ? 'Modifier un lien de menu' : 'Ajouter un lien de menu';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 600px;">
    <form action="<?= BASE_URL ?>/admin/navigation/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Libellé affiché</label>
            <input type="text" name="label" class="form-control" value="<?= e($link['label']) ?>" maxlength="60" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Lien</label>
            <input type="text" name="url" class="form-control" value="<?= e($link['url']) ?>" placeholder="/programmes.php ou https://..." required>
            <div class="form-text">Chemin interne (ex: <code>/faq.php</code>) ou URL externe complète.</div>
        </div>

        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" name="is_visible" id="isVisible" value="1" <?= $link['is_visible'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="isVisible">Visible dans le menu</label>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/navigation/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
