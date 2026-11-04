<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$highlights = $pdo->query('SELECT * FROM campus_highlights ORDER BY sort_order ASC, created_at ASC')->fetchAll();

$pageTitle = 'Campus (page À propos)';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Fiches campus — page À propos</h5>
        <p class="text-muted small mb-0">HITAS reste présenté dans son ensemble sur le site (pas de contenu séparé par campus) : ces fiches ne servent qu'à mettre en avant un campus en particulier — comme celui de Yaoundé — juste sous « Notre mission ». Vous pouvez en ajouter d'autres au fil du temps.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/campus_highlights/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une fiche</a>
</div>

<div class="row g-3">
    <?php foreach ($highlights as $h): ?>
        <div class="col-6 col-md-3">
            <div class="card p-2 gallery-admin-card">
                <?php if (!empty($h['photo'])): ?>
                    <img src="<?= UPLOAD_URL ?>campus_highlights/<?= e($h['photo']) ?>" class="w-100 rounded mb-2" style="height:140px;object-fit:cover;">
                <?php else: ?>
                    <div class="w-100 rounded mb-2 bg-light d-flex align-items-center justify-content-center text-muted" style="height:140px;"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <div class="small fw-semibold"><?= e($h['name'] ?: 'Sans nom') ?></div>
                <div class="small text-muted mb-2">
                    Ordre : <?= (int)$h['sort_order'] ?>
                    <br>
                    <?= $h['is_published'] ? '<span class="text-success">Publiée</span>' : '<span class="text-muted">Masquée</span>' ?>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <a href="<?= BASE_URL ?>/admin/campus_highlights/form.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-pencil"></i> Modifier</a>
                </div>
                <form action="<?= BASE_URL ?>/admin/campus_highlights/delete.php" method="post" class="js-confirm-delete">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Supprimer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$highlights): ?>
        <p class="text-muted">Aucune fiche pour le moment. La section « Nos campus » reste masquée sur la page À propos tant qu'aucune fiche publiée n'existe.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
