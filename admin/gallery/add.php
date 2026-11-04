<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Ajouter une photo';
$programs = $pdo->query('SELECT id, title FROM programs WHERE is_published = 1 ORDER BY title')->fetchAll();
$partners = $pdo->query('SELECT id, name FROM partners WHERE is_published = 1 ORDER BY name')->fetchAll();
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/gallery/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">Titre (optionnel)</label>
            <input type="text" name="title" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Catégorie (ex : Campus, Chambres, Cantine...)</label>
            <input type="text" name="category" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Programme lié (optionnel)</label>
            <select name="program_id" class="form-select">
                <option value="">Aucun</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= (int)$program['id'] ?>"><?= e($program['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Partenaire lié (optionnel)</label>
            <select name="partner_id" class="form-select">
                <option value="">Aucun</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?= (int)$partner['id'] ?>"><?= e($partner['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Par exemple : salle d’étude moderne, chambres partagées, cantine internationale..."></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="<?= BASE_URL ?>/admin/gallery/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
