<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$highlight = ['name' => '', 'location' => '', 'title' => '', 'photo' => '', 'short_description' => '', 'full_description' => '', 'is_published' => 1, 'sort_order' => 0];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM campus_highlights WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        die('Fiche introuvable.');
    }
    $highlight = $found;
}

$pageTitle = $id ? 'Modifier une fiche campus' : 'Ajouter une fiche campus';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <p class="text-muted small">Cette fiche s'affiche sur la page À propos, juste sous « Notre mission ». HITAS reste présenté dans son ensemble ; cette fiche sert uniquement à mettre en avant un campus particulier (ex : Yaoundé).</p>
    <form action="<?= BASE_URL ?>/admin/campus_highlights/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nom du campus</label>
            <input type="text" name="name" class="form-control" value="<?= e($highlight['name']) ?>" placeholder="ex : Campus de Yaoundé" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Localisation</label>
            <input type="text" name="location" class="form-control" value="<?= e($highlight['location'] ?? '') ?>" placeholder="ex : Yaoundé, quartier Bastos">
            <div class="form-text">Ville / quartier affiché sur la fiche, sous le nom du campus.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Titre affiché</label>
            <input type="text" name="title" class="form-control" value="<?= e($highlight['title']) ?>" placeholder="ex : Notre implantation au cœur du Centre">
        </div>

        <div class="mb-3">
            <label class="form-label">Mini description</label>
            <input type="text" name="short_description" class="form-control" maxlength="300" value="<?= e($highlight['short_description']) ?>" placeholder="Une phrase courte, affichée directement sur la fiche." required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description détaillée (bouton « Plus de détails »)</label>
            <textarea name="full_description" class="form-control" rows="6" placeholder="Rôle du campus, spécificités, filières accueillies..."><?= e($highlight['full_description']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <?php if (!empty($highlight['photo'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>campus_highlights/<?= e($highlight['photo']) ?>" class="thumb-preview" style="width:160px;height:110px;object-fit:cover;border-radius:6px;"></div>
            <?php endif; ?>
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp" <?= $id ? '' : 'required' ?>>
            <?php if ($id): ?>
                <div class="form-text">Laisser vide pour conserver la photo actuelle.</div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Statut</label>
                <select name="is_published" class="form-select">
                    <option value="1" <?= $highlight['is_published'] ? 'selected' : '' ?>>Publiée</option>
                    <option value="0" <?= !$highlight['is_published'] ? 'selected' : '' ?>>Masquée</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Ordre d'affichage</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)$highlight['sort_order'] ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= $id ? 'Mettre à jour' : 'Ajouter' ?></button>
        <a href="<?= BASE_URL ?>/admin/campus_highlights/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
