<?php
require_once __DIR__ . '/includes/site_helpers.php';

$slug = $_GET['slug'] ?? '';
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM news WHERE slug = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    die('Article introuvable.');
}

if (!empty($article['is_advertisement']) && !empty($article['external_link'])) {
    header('Location: ' . $article['external_link']);
    exit;
}

$pageTitle = $article['title'] . ' — HITAS';
$pageDescription = $article['excerpt'] ?: ('Actualité HITAS : ' . $article['title']);
applySeoOverrides($article, 'news');
$currentPage = 'actualites';
$pageBackgroundKey = 'actualites';

// Médias de l'actualité (gérés depuis l'admin > Actualités) : la vidéo
// éventuelle en premier (elle prend le pas sur l'image de couverture),
// puis la photo de couverture, puis les autres photos dans leur ordre.
$stmt = $pdo->prepare("SELECT image FROM news_images WHERE news_id = ? ORDER BY is_cover DESC, sort_order ASC, id ASC");
$stmt->execute([$article['id']]);
$photoFiles = array_column($stmt->fetchAll(), 'image');
if (!$photoFiles && $article['image']) {
    $photoFiles = [$article['image']];
}
$coverFile = $photoFiles[0] ?? null;

$lbText = trim((string)$article['excerpt']);
if ($lbText === '') {
    $lbText = trim(preg_replace('/\s+/', ' ', (string)$article['content']));
    if (mb_strlen($lbText) > 260) {
        $lbText = rtrim(mb_substr($lbText, 0, 260)) . '…';
    }
}

$mediaItems = [];
if (!empty($article['video'])) {
    $mediaItems[] = [
        'type'   => 'video',
        'src'    => UPLOAD_URL . 'news/' . $article['video'],
        'poster' => $coverFile ? UPLOAD_URL . 'news/' . $coverFile : '',
    ];
}
foreach ($photoFiles as $f) {
    $mediaItems[] = ['type' => 'image', 'src' => UPLOAD_URL . 'news/' . $f, 'poster' => ''];
}

include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/actualites.php">Actualités</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($article['title']) ?></li>
            </ol>
        </nav>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="eyebrow text-white">Actualité</span>
                <h1 class="display-font mt-3"><?= e($article['title']) ?></h1>
                <p class="lead text-white-75"><?= e($article['excerpt'] ?: 'Aperçu de l’article en cours de rédaction.') ?></p>
                <div class="text-white-75 mt-3"><small><?= date('d/m/Y', strtotime($article['published_at'])) ?></small></div>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm p-4">
                    <?php if ($mediaItems): $multi = count($mediaItems) > 1; $first = $mediaItems[0]; ?>
                        <div class="article-stage mb-4" data-stage>
                            <div class="article-stage-frame">
                            <div class="article-stage-view" data-stage-view>
                                <?php /* Affichage de secours sans JavaScript : le premier média. */ ?>
                                <?php if ($first['type'] === 'video'): ?>
                                    <video class="stage-media" src="<?= e($first['src']) ?>" <?= $first['poster'] ? 'poster="' . e($first['poster']) . '"' : '' ?> controls playsinline preload="metadata"></video>
                                <?php else: ?>
                                    <img class="stage-media" src="<?= e($first['src']) ?>" alt="<?= e($article['title']) ?>">
                                <?php endif; ?>
                            </div>
                            <?php if ($multi): ?>
                                <button type="button" class="stage-nav stage-nav--prev" data-stage-prev aria-label="Média précédent"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="stage-nav stage-nav--next" data-stage-next aria-label="Média suivant"><i class="bi bi-chevron-right"></i></button>
                                <span class="stage-count" data-stage-count>1 / <?= count($mediaItems) ?></span>
                            <?php endif; ?>
                            <button type="button" class="stage-expand" data-stage-expand><i class="bi bi-arrows-fullscreen"></i> Agrandir</button>
                            </div>
                            <?php if ($multi): ?>
                                <div class="article-stage-thumbs">
                                    <?php foreach ($mediaItems as $k => $m): ?>
                                        <?php $thumbUrl = $m['type'] === 'video' ? $m['poster'] : $m['src']; ?>
                                        <button type="button" class="stage-thumb" data-stage-thumb
                                                data-lb-type="<?= e($m['type']) ?>"
                                                data-lb-src="<?= e($m['src']) ?>"
                                                data-lb-poster="<?= e($m['poster']) ?>"
                                                data-lb-thumb="<?= e($thumbUrl) ?>"
                                                data-lb-title="<?= e($article['title']) ?>"
                                                data-lb-text="<?= e($lbText) ?>"
                                                style="<?= $thumbUrl ? "background-image:url('" . e($thumbUrl) . "')" : '' ?>"
                                                aria-label="<?= $m['type'] === 'video' ? 'Voir la vidéo' : 'Voir la photo ' . ($k + 1) ?>">
                                            <?php if ($m['type'] === 'video'): ?><i class="bi bi-play-circle-fill"></i><?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span hidden data-stage-thumb
                                      data-lb-type="<?= e($first['type']) ?>"
                                      data-lb-src="<?= e($first['src']) ?>"
                                      data-lb-poster="<?= e($first['poster']) ?>"
                                      data-lb-title="<?= e($article['title']) ?>"
                                      data-lb-text="<?= e($lbText) ?>"></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="article-content">
                        <?= nl2br(e($article['content'] ?: 'Le contenu complet de cet article sera bientôt disponible.')) ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm p-4">
                    <h3 class="h5 mb-3">Besoin d'aide ?</h3>
                    <p class="small text-muted mb-3">Contactez notre équipe pour recevoir des informations personnalisées sur nos programmes et nos partenaires.</p>
                    <a href="#footer-phone-contact" class="btn btn-hitas-primary w-100">Nous contacter</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site_footer.php';
