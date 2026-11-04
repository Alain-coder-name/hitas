<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$stat = null;
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM stats WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $stat = $stmt->fetch();
}

$commonIcons = ['bi-mortarboard', 'bi-globe2', 'bi-geo-alt', 'bi-people', 'bi-award', 'bi-building', 'bi-flag', 'bi-calendar-check', 'bi-star'];

$pageTitle = $stat ? 'Modifier le chiffre clé' : 'Ajouter un chiffre clé';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 640px;">
    <form action="<?= BASE_URL ?>/admin/stats/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($stat): ?><input type="hidden" name="id" value="<?= (int)$stat['id'] ?>"><?php endif; ?>

        <div class="row">
            <div class="col-md-5 mb-3">
                <label class="form-label">Valeur</label>
                <input type="text" name="value" class="form-control" placeholder="ex. 14, 500+, 8 pays" value="<?= e($stat['value'] ?? '') ?>" required>
            </div>
            <div class="col-md-7 mb-3">
                <label class="form-label">Libellé</label>
                <input type="text" name="label" class="form-control" placeholder="ex. Programmes" value="<?= e($stat['label'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Icône (facultatif — nom d'icône Bootstrap Icons)</label>
            <input type="text" name="icon" class="form-control" list="statIcons" placeholder="ex. bi-mortarboard" value="<?= e($stat['icon'] ?? '') ?>">
            <datalist id="statIcons">
                <?php foreach ($commonIcons as $ic): ?><option value="<?= e($ic) ?>"><?php endforeach; ?>
            </datalist>
            <div class="form-text">Voir la liste complète sur <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">icons.getbootstrap.com</a>.</div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" id="isActive" class="form-check-input" <?= (!$stat || $stat['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Visible sur le site</label>
        </div>

        <button type="submit" class="btn btn-primary"><?= $stat ? 'Enregistrer' : 'Ajouter' ?></button>
        <a href="<?= BASE_URL ?>/admin/stats/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
