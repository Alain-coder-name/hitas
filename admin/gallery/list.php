<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$images = $pdo->query('SELECT g.*, pr.title AS program_title, p.name AS partner_name
    FROM gallery g
    LEFT JOIN programs pr ON pr.id = g.program_id
    LEFT JOIN partners p ON p.id = g.partner_id
    ORDER BY g.category ASC, g.sort_order ASC, g.created_at DESC')->fetchAll();

$pageTitle = 'Galerie & Médiathèque';
include __DIR__ . '/../header.php';
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" href="<?= BASE_URL ?>/admin/gallery/list.php"><i class="bi bi-images"></i> Galerie (photos vie étudiante)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/admin/gallery/media.php"><i class="bi bi-hdd-stack"></i> Médiathèque (tous les fichiers)</a>
    </li>
</ul>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Galerie photo (vie étudiante / campus)</h5>
    <a href="<?= BASE_URL ?>/admin/gallery/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une photo ou une vidéo</a>
</div>

<p class="text-muted small">Sur la page À propos, chaque catégorie défile en diaporama par groupes de 6 photos (comme "Filières phares" sur l'accueil). L'ordre d'affichage détermine quelles photos apparaissent dans le premier groupe.</p>

<div class="row g-3">
    <?php foreach ($images as $img): ?>
        <div class="col-6 col-md-3">
            <div class="card p-2 gallery-admin-card">
                <?php if (!empty($img['image'])): ?>
                    <img src="<?= UPLOAD_URL ?>gallery/<?= e($img['image']) ?>" class="w-100 rounded mb-2" style="height:140px;object-fit:cover;">
                <?php elseif (!empty($img['video'])): ?>
                    <video src="<?= UPLOAD_URL ?>gallery/<?= e($img['video']) ?>#t=0.5" class="w-100 rounded mb-2" style="height:140px;object-fit:cover;" preload="metadata" muted></video>
                <?php endif; ?>
                <?php if (!empty($img['video'])): ?><div class="small mb-1"><span class="badge bg-dark"><i class="bi bi-play-fill"></i> Vidéo</span></div><?php endif; ?>
                <div class="small fw-semibold"><?= e($img['title'] ?: 'Sans titre') ?></div>
                <div class="small text-muted mb-2"><?= e($img['category']) ?: '—' ?> · ordre <?= (int)$img['sort_order'] ?></div>
                <?php if ($img['program_title']): ?>
                    <div class="small text-muted mb-1">Programme : <?= e($img['program_title']) ?></div>
                <?php endif; ?>
                <?php if ($img['partner_name']): ?>
                    <div class="small text-muted mb-1">Partenaire : <?= e($img['partner_name']) ?></div>
                <?php endif; ?>
                <?php if ($img['description']): ?>
                    <div class="small text-muted mb-2"><?= e($img['description']) ?></div>
                <?php endif; ?>
                <div class="d-flex gap-2 mb-2">
                    <a href="<?= BASE_URL ?>/admin/gallery/form.php?id=<?= (int)$img['id'] ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-pencil"></i> Modifier</a>
                </div>
                <form action="<?= BASE_URL ?>/admin/gallery/delete.php" method="post" class="js-confirm-delete">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Supprimer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$images): ?>
        <p class="text-muted">Aucune photo pour le moment.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
