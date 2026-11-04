<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$pages = $pdo->query('SELECT * FROM pages ORDER BY title')->fetchAll();

$pageTitle = 'Pages statiques';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Contenu des pages statiques</h5>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Titre</th><th>Dernière modification</th><th style="width:100px;"></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><?= e($p['title']) ?></td>
                <td><?= e($p['updated_at']) ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/pages/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-muted small mb-0">Ces pages correspondent aux contenus fixes du site (À propos, Mission, Admissions...). D'autres pages statiques peuvent être ajoutées directement en base si besoin.</p>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
