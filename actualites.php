<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$articles = $pdo->query('SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC')->fetchAll();

// "HITAS dans l'actualité internationale" — rubrique totalement séparée
// des actualités/publicités ci-dessus (table indépendante, photos propres).
$pressMentions = $pdo->query('SELECT * FROM press_mentions WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Actualités — HITAS';
$pageDescription = 'Actualités, événements et opportunités pour les étudiants et les filières de HITAS.';
applySeoOverrides(getPageSeo('actualites'));
$currentPage = 'actualites';
$pageBackgroundKey = 'actualites';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb hitas-breadcrumb--light">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Actualités</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow"><?= e(getSetting('actualites_hero_eyebrow', 'Actualités')) ?></span>
                <h1 class="display-font mt-3"><?= e(getSetting('actualites_hero_title', 'Les dernières nouvelles de HITAS')) ?></h1>
                <p class="lead text-muted"><?= e(getSetting('actualites_hero_lead', 'Restez informé des actualités, événements et opportunités pour nos étudiants et nos filières.')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <?php if (!$articles): ?>
            <div class="alert alert-warning">Aucune actualité publiée pour le moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 col-lg-4 program-card-cell">
                        <div class="program-card">
                            <div class="program-img" style="<?= $article['image'] ? "background-image:url('" . UPLOAD_URL . "news/" . e($article['image']) . "')" : '' ?>">
                                <?php if (!empty($article['video'])): ?>
                                    <?php if (!$article['image']): ?>
                                        <video class="program-img-video" src="<?= UPLOAD_URL ?>news/<?= e($article['video']) ?>#t=0.5" preload="metadata" muted playsinline aria-hidden="true"></video>
                                    <?php endif; ?>
                                    <span class="media-play-badge" aria-hidden="true"><i class="bi bi-play-fill"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="small text-muted"><?= date('d/m/Y', strtotime($article['published_at'])) ?></div>
                                    <?php if ($article['is_advertisement']): ?>
                                        <span class="badge bg-warning text-dark">Publicité</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Actualité</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="h5 mb-2"><?= e($article['title']) ?></h3>
                                <p class="text-muted mb-3"><?= e($article['excerpt'] ?: 'Résumé en cours de rédaction.') ?></p>
                                <a href="<?= e(newsTargetUrl($article)) ?>" <?= newsTargetAttrs($article) ?> class="stretched-link text-decoration-none fw-semibold"><?= ($article['is_advertisement'] && !empty($article['external_link'])) ? 'Découvrir l’offre' : (!empty($article['video']) ? 'Voir la vidéo' : 'Lire l’article') ?> <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($pressMentions): ?>
<section class="content-section py-5 reveal press-section">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-eyebrow">Rayonnement international</span>
            <h2 class="section-title">HITAS dans l'actualité internationale</h2>
            <p class="text-muted mx-auto" style="max-width:680px;">HITAS fait aussi parler d'elle au-delà de ses murs — retrouvez ici les mentions et retombées presse à l'international.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($pressMentions as $mention): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="press-card h-100">
                        <div class="press-card-img" style="<?= $mention['image'] ? "background-image:url('" . UPLOAD_URL . "press/" . e($mention['image']) . "')" : '' ?>">
                            <?php if ($mention['source']): ?><span class="press-card-source"><?= e($mention['source']) ?></span><?php endif; ?>
                        </div>
                        <div class="press-card-body">
                            <?php if ($mention['published_date']): ?>
                                <div class="press-card-date"><?= e(date('d/m/Y', strtotime($mention['published_date']))) ?></div>
                            <?php endif; ?>
                            <h3 class="h6 mb-2"><?= e($mention['title']) ?></h3>
                            <?php if ($mention['excerpt']): ?>
                                <p class="text-muted small mb-3"><?= e($mention['excerpt']) ?></p>
                            <?php endif; ?>
                            <?php if ($mention['external_link']): ?>
                                <a href="<?= e($mention['external_link']) ?>" target="_blank" rel="noopener noreferrer" class="small fw-semibold">Lire l'article original <i class="bi bi-box-arrow-up-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/site_footer.php';
