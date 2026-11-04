<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['title' => '', 'category' => '', 'description' => '', 'program_id' => null, 'partner_id' => null, 'image' => '', 'sort_order' => 0];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gallery WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        die('Photo introuvable.');
    }
    $item = $found;
}

$programs = $pdo->query('SELECT id, title FROM programs WHERE is_published = 1 ORDER BY title')->fetchAll();
$partners = $pdo->query('SELECT id, name FROM partners WHERE is_published = 1 ORDER BY name')->fetchAll();

$pageTitle = $id ? 'Modifier une photo' : 'Ajouter une photo';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/gallery/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Titre (optionnel)</label>
            <input type="text" name="title" class="form-control" value="<?= e($item['title']) ?>">
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Catégorie (ex : Campus, Chambres, Cantine...)</label>
                <input type="text" name="category" class="form-control" value="<?= e($item['category']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Ordre d'affichage</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>">
                <div class="form-text">Détermine l'ordre dans le diaporama de sa catégorie (par groupes de 6), sur la page À propos.</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Programme lié (optionnel)</label>
            <select name="program_id" class="form-select">
                <option value="">Aucun</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= (int)$program['id'] ?>" <?= $item['program_id'] == $program['id'] ? 'selected' : '' ?>><?= e($program['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Partenaire lié (optionnel)</label>
            <select name="partner_id" class="form-select">
                <option value="">Aucun</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?= (int)$partner['id'] ?>" <?= $item['partner_id'] == $partner['id'] ? 'selected' : '' ?>><?= e($partner['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Par exemple : salle d’étude moderne, chambres partagées, cantine internationale..."><?= e($item['description']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <?php if (!empty($item['image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>gallery/<?= e($item['image']) ?>" class="thumb-preview" style="width:120px;height:90px;object-fit:cover;"></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" <?= $id ? '' : 'required' ?>>
            <?php if ($id): ?>
                <div class="form-text">Laisser vide pour conserver l’image actuelle.</div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary"><?= $id ? 'Mettre à jour' : 'Ajouter' ?></button>
        <a href="<?= BASE_URL ?>/admin/gallery/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
