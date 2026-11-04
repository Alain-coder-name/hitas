<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();

// Pages "SEO uniquement" créées par le Lot A : pas de contenu propre, on les masque
// de cette liste si elles n'ont ni titre de contenu significatif ni intérêt à afficher deux fois.
$pages = $pdo->query("SELECT * FROM pages ORDER BY title")->fetchAll();
$programs = $pdo->query("SELECT id, title, slug, meta_title FROM programs ORDER BY title")->fetchAll();
$news = $pdo->query("SELECT id, title, slug, meta_title FROM news ORDER BY title")->fetchAll();

$pageTitle = 'SEO';
include __DIR__ . '/../header.php';
?>

<div class="alert alert-info small mb-4">
    Titre, description et image de partage (Open Graph) affichés par les moteurs de recherche
    et les réseaux sociaux pour chaque page, programme ou actualité. Si un champ est laissé vide,
    le site utilise son texte par défaut.
</div>

<div class="card p-3 mb-4">
    <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Pages</h6>
    <table class="table align-middle mb-0">
        <thead><tr><th>Page</th><th>Titre SEO</th><th style="width:100px;"></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><?= e($p['title']) ?></td>
                <td class="text-muted small"><?= e($p['meta_title'] ?: '— (par défaut)') ?></td>
                <td class="text-end"><a href="<?= BASE_URL ?>/admin/seo/edit.php?type=page&id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card p-3 mb-4">
    <h6 class="mb-3"><i class="bi bi-mortarboard"></i> Programmes</h6>
    <table class="table align-middle mb-0">
        <thead><tr><th>Programme</th><th>Slug (URL)</th><th>Titre SEO</th><th style="width:100px;"></th></tr></thead>
        <tbody>
        <?php foreach ($programs as $p): ?>
            <tr>
                <td><?= e($p['title']) ?></td>
                <td class="text-muted small"><?= e($p['slug']) ?></td>
                <td class="text-muted small"><?= e($p['meta_title'] ?: '— (par défaut)') ?></td>
                <td class="text-end"><a href="<?= BASE_URL ?>/admin/seo/edit.php?type=program&id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$programs): ?><tr><td colspan="4" class="text-center text-muted py-3">Aucun programme.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-newspaper"></i> Actualités</h6>
    <table class="table align-middle mb-0">
        <thead><tr><th>Actualité</th><th>Slug (URL)</th><th>Titre SEO</th><th style="width:100px;"></th></tr></thead>
        <tbody>
        <?php foreach ($news as $n): ?>
            <tr>
                <td><?= e($n['title']) ?></td>
                <td class="text-muted small"><?= e($n['slug']) ?></td>
                <td class="text-muted small"><?= e($n['meta_title'] ?: '— (par défaut)') ?></td>
                <td class="text-end"><a href="<?= BASE_URL ?>/admin/seo/edit.php?type=news&id=<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$news): ?><tr><td colspan="4" class="text-center text-muted py-3">Aucune actualité.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
