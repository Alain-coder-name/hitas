<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'hitas.org') . BASE_URL;

$staticPages = [
    ['loc' => '/index.php', 'priority' => '1.0'],
    ['loc' => '/a-propos.php', 'priority' => '0.7'],
    ['loc' => '/programmes.php', 'priority' => '0.9'],
    ['loc' => '/partenaires.php', 'priority' => '0.7'],
    ['loc' => '/actualites.php', 'priority' => '0.7'],
    ['loc' => '/equipe.php', 'priority' => '0.5'],
    ['loc' => '/faq.php', 'priority' => '0.6'],
    ['loc' => '/postulez.php', 'priority' => '0.8'],
];

$programs = $pdo->query("SELECT slug, updated_at FROM programs WHERE is_published = 1")->fetchAll();
$partners = $pdo->query("SELECT id FROM partners WHERE is_published = 1")->fetchAll();
$news     = $pdo->query("SELECT slug, published_at, is_advertisement, external_link FROM news WHERE is_published = 1")->fetchAll();

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($staticPages as $page): ?>
    <url>
        <loc><?= e($baseUrl . $page['loc']) ?></loc>
        <priority><?= e($page['priority']) ?></priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($programs as $program): ?>
    <url>
        <loc><?= e($baseUrl . '/programme.php?slug=' . urlencode($program['slug'])) ?></loc>
        <?php if (!empty($program['updated_at'])): ?><lastmod><?= e(date('Y-m-d', strtotime($program['updated_at']))) ?></lastmod><?php endif; ?>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($partners as $partner): ?>
    <url>
        <loc><?= e($baseUrl . '/partenaire.php?id=' . (int)$partner['id']) ?></loc>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($news as $article): ?>
        <?php if (!empty($article['is_advertisement']) && !empty($article['external_link'])) continue; // les publicités pointent hors du site, pas de sens de les indexer ?>
    <url>
        <loc><?= e($baseUrl . '/article.php?slug=' . urlencode($article['slug'])) ?></loc>
        <?php if (!empty($article['published_at'])): ?><lastmod><?= e(date('Y-m-d', strtotime($article['published_at']))) ?></lastmod><?php endif; ?>
        <priority>0.5</priority>
    </url>
    <?php endforeach; ?>
</urlset>
