<?php
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/filiere_classifier.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) {
    header('HTTP/1.0 404 Not Found');
    die('Partenaire introuvable.');
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM partners WHERE id = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$id]);
$partner = $stmt->fetch();

if (!$partner) {
    header('HTTP/1.0 404 Not Found');
    die('Partenaire introuvable.');
}

$programStmt = $pdo->prepare('SELECT pr.*, pp.partner_url FROM programs pr
    JOIN program_partners pp ON pp.program_id = pr.id
    WHERE pp.partner_id = ? AND pr.is_published = 1
    ORDER BY pr.title');
$programStmt->execute([$id]);
$programs = $programStmt->fetchAll();

// Lien précis vers la page (chez le partenaire) de chaque filière : priorité à
// l'URL renseignée au cas par cas (partner_url), sinon la page dédiée au cycle
// (Ingénierie & Technologies, etc.) chez ce partenaire, sinon son site officiel.
$cycleLinkStmt = $pdo->prepare('SELECT category, url FROM partner_cycle_links WHERE partner_id = ?');
$cycleLinkStmt->execute([$id]);
$partnerCycleLinks = [];
foreach ($cycleLinkStmt->fetchAll() as $row) {
    $partnerCycleLinks[$row['category']] = $row['url'];
}

function partnerFiliereLink(array $program, array $partner): ?string
{
    $direct = trim((string)($program['partner_url'] ?? ''));
    if ($direct !== '') return $direct;
    // Pas de repli sur le site du partenaire pour les partenaires en
    // co-diplomation directe (ex. Université de Ngaoundéré) : l'étudiant
    // s'inscrit chez HITAS, pas chez eux — un lien externe serait trompeur.
    if (!empty($partner['co_diplomation_note'])) return null;
    return $partner['website'] ?: null;
}

function partnerCycleLink(string $category, array $partnerCycleLinks, array $partner): ?string
{
    if (!empty($partnerCycleLinks[$category])) return $partnerCycleLinks[$category];
    if (!empty($partner['co_diplomation_note'])) return null;
    return $partner['website'] ?: null;
}

// Regroupement des filières de ce partenaire par Niveau -> Filière, avec
// spécialités triées de A à Z, pour rester cohérent avec l'organisation
// utilisée sur la page Programmes et dans l'admin.
$programsGrouped = [];
foreach ($programs as $p) {
    $niveau = $p['degree_level'] ?: 'Autre';
    $filiere = trim((string)($p['category'] ?? '')) ?: 'Autres filières';
    $programsGrouped[$niveau][$filiere][] = $p;
}
ksort($programsGrouped, SORT_STRING | SORT_FLAG_CASE);
foreach ($programsGrouped as &$programsGroupedFilieres) {
    ksort($programsGroupedFilieres, SORT_STRING | SORT_FLAG_CASE);
    foreach ($programsGroupedFilieres as &$programsGroupedSpecialites) {
        usort($programsGroupedSpecialites, fn($a, $b) => strcasecmp($a['title'], $b['title']));
    }
    unset($programsGroupedSpecialites);
}
unset($programsGroupedFilieres);

// Ordre d'affichage fixe des niveaux, du plus élémentaire au plus avancé
// (BTS/DUT -> Licence/Bachelor -> Ingénierie -> Master -> Doctorat), pour
// que le visiteur comprenne immédiatement la progression académique — même
// logique partout, quel que soit le partenaire. Tout niveau imprévu (pas
// dans cette liste) est ajouté ensuite, sans jamais rien masquer.
$niveauDisplayOrder = [
    'Programme Foundation',
    'BTS',
    'DUT',
    'Licence',
    'Bachelor',
    'Licence Professionnelle',
    "Diplôme QUALIFI (jusqu'au niveau 6)",
    "Diplôme d'Ingénieur des Travaux",
    "Diplôme d'Ingénieur",
    "Programme Pre-Master's",
    'Master',
    'Doctorat',
    'Doctorat en Médecine Vétérinaire',
];
$orderedNiveaux = array_merge(
    array_values(array_intersect($niveauDisplayOrder, array_keys($programsGrouped))),
    array_diff(array_keys($programsGrouped), $niveauDisplayOrder)
);
$niveauStyles = [
    'Programme Foundation'                => ['color' => '#2E86DE', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-signpost-2'],
    'BTS'                                  => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-tools'],
    'DUT'                                  => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-tools'],
    'Licence'                              => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-mortarboard'],
    'Bachelor'                             => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-mortarboard'],
    'Licence Professionnelle'              => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-mortarboard'],
    "Diplôme QUALIFI (jusqu'au niveau 6)" => ['color' => 'var(--hitas-navy)', 'bg' => 'rgba(46,134,222,0.07)', 'icon' => 'bi-patch-check'],
    "Diplôme d'Ingénieur des Travaux"     => ['color' => '#1f7a5c', 'bg' => 'rgba(31,122,92,0.08)', 'icon' => 'bi-gear'],
    "Diplôme d'Ingénieur"                  => ['color' => '#1f7a5c', 'bg' => 'rgba(31,122,92,0.08)', 'icon' => 'bi-gear-fill'],
    "Programme Pre-Master's"              => ['color' => 'var(--hitas-gold)', 'bg' => 'rgba(156,124,60,0.09)', 'icon' => 'bi-signpost-split'],
    'Master'                               => ['color' => 'var(--hitas-gold)', 'bg' => 'rgba(156,124,60,0.09)', 'icon' => 'bi-mortarboard-fill'],
    'Doctorat'                             => ['color' => '#8a4b2a', 'bg' => 'rgba(138,75,42,0.09)', 'icon' => 'bi-mortarboard-fill'],
    'Doctorat en Médecine Vétérinaire'    => ['color' => '#8a4b2a', 'bg' => 'rgba(138,75,42,0.09)', 'icon' => 'bi-mortarboard-fill'],
];

$galleryStmt = $pdo->prepare('SELECT * FROM gallery WHERE partner_id = ? ORDER BY created_at DESC');
$galleryStmt->execute([$id]);
$partnerGallery = $galleryStmt->fetchAll();

$pageTitle = $partner['name'] . ' — Partenaire HITAS';
$pageDescription = $partner['description'] ?: ($partner['name'] . ', partenaire de HITAS en ' . $partner['country'] . '.');
$currentPage = 'partenaires';
$pageBackgroundKey = 'partenaires';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero partenaire-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/partenaires.php">Partenaires</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($partner['name']) ?></li>
            </ol>
        </nav>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="eyebrow text-white">Partenaire</span>
                <h1 class="display-font mt-3"><?= e($partner['name']) ?></h1>
                <p class="lead text-white-75"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></p>
                <?php if (!empty($partner['degree_level'])): ?>
                    <span class="badge bg-hitas-gold-badge mb-2"><?= e($partner['degree_level']) ?></span>
                <?php endif; ?>
                <?php if ($partner['website']): ?>
                    <p class="mt-3 mb-0"><a href="<?= e($partner['website']) ?>" target="_blank" rel="noopener" class="text-white text-decoration-underline"><?= e(str_replace(['https://', 'http://'], '', $partner['website'])) ?></a></p>
                <?php endif; ?>
            </div>
            <?php if ($partner['logo']): ?>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="partner-hero-logo" loading="lazy">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="content-section py-5 partenaire-detail-section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="card shadow-sm p-4 mb-4 partner-bio-card">
                    <span class="section-eyebrow">Présentation</span>
                    <h2 class="partner-section-title mt-2 mb-3">Qui est <?= e($partner['name']) ?> ?</h2>
                    <div class="partner-meta-row mb-3">
                        <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt"></i> <?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></span>
                        <?php if (!empty($partner['degree_level'])): ?><span class="badge bg-hitas-gold-badge"><?= e($partner['degree_level']) ?></span><?php endif; ?>
                        <?php if ($programs): ?><span class="badge bg-light text-dark border"><i class="bi bi-mortarboard"></i> <?= count($programs) ?> filière<?= count($programs) > 1 ? 's' : '' ?></span><?php endif; ?>
                    </div>
                    <?php if ($partner['description']): ?>
                        <div class="partner-description">
                            <?= textToParagraphs($partner['description']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">La fiche de ce partenaire sera complétée prochainement par notre équipe.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($partner['co_diplomation_note'])): ?>
                    <div class="card shadow-sm p-4 mb-4 partner-codiplomation-card">
                        <span class="section-eyebrow">Diplôme délivré via HITAS</span>
                        <h3 class="partner-section-title mt-2 mb-3"><i class="bi bi-patch-check-fill"></i> Diplôme ou co-diplômation</h3>
                        <div class="partner-description">
                            <?= textToParagraphs($partner['co_diplomation_note']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($programs): ?>
                    <div class="card shadow-sm p-4 partner-programs-card">
                        <span class="section-eyebrow">Au programme</span>
                        <h3 class="partner-section-title mt-2 mb-1">Filières proposées</h3>
                        <p class="text-muted small mb-4"><?= count($programs) ?> filière<?= count($programs) > 1 ? 's' : '' ?> disponible<?= count($programs) > 1 ? 's' : '' ?> chez <?= e($partner['name']) ?>, classées par niveau puis par filière.</p>

                        <?php foreach ($orderedNiveaux as $niveau): ?>
                            <?php
                            $style = $niveauStyles[$niveau] ?? ['color' => '#374151', 'bg' => '#f3f4f6', 'icon' => 'bi-mortarboard'];
                            $filieresForNiveau = $programsGrouped[$niveau];
                            $countForNiveau = array_sum(array_map('count', $filieresForNiveau));
                            ?>
                            <div class="degree-level-block" style="--level-color: <?= $style['color'] ?>; --level-bg: <?= $style['bg'] ?>;">
                                <div class="degree-level-header">
                                    <i class="bi <?= $style['icon'] ?>"></i>
                                    <span><?= e($niveau) ?></span>
                                    <span class="degree-level-count"><?= $countForNiveau ?> filière<?= $countForNiveau > 1 ? 's' : '' ?></span>
                                </div>
                                <?php foreach ($filieresForNiveau as $filiere => $specialites): ?>
                                    <?php $cycleUrl = partnerCycleLink($filiere, $partnerCycleLinks, $partner); ?>
                                    <div class="filiere-group">
                                        <h4 class="filiere-group-title">
                                            <?php if ($cycleUrl): ?>
                                                <a href="<?= e($cycleUrl) ?>" target="_blank" rel="noopener" class="filiere-group-title-link"><?= e($filiere) ?> <i class="bi bi-box-arrow-up-right small"></i></a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/programmes.php#cycle-<?= e(str_replace(' ', '-', normalizeFiliereText($filiere))) ?>" class="filiere-group-title-link"><?= e($filiere) ?></a>
                                            <?php endif; ?>
                                            <span class="filiere-group-count">(<?= count($specialites) ?>)</span>
                                        </h4>
                                        <div class="row g-3">
                                            <?php foreach ($specialites as $program): ?>
                                                <?php $programUrl = partnerFiliereLink($program, $partner); ?>
                                                <div class="col-md-6">
                                                    <div class="partner-associated-program h-100">
                                                        <h5 class="h6 mb-2"><?= e($program['title']) ?></h5>
                                                        <?php if ($program['duration']): ?><span class="partner-program-badge mb-2"><i class="bi bi-clock"></i> <?= e($program['duration']) ?></span><?php endif; ?>
                                                        <p class="text-muted small mt-2 mb-2"><?= e($program['short_description'] ?: 'Description en cours de rédaction.') ?></p>
                                                        <?php if ($programUrl): ?>
                                                            <a href="<?= e($programUrl) ?>" target="_blank" rel="noopener" class="small fw-semibold">Voir chez le partenaire <i class="bi bi-box-arrow-up-right"></i></a>
                                                        <?php else: ?>
                                                            <a href="<?= BASE_URL ?>/programme.php?slug=<?= urlencode($program['slug']) ?>" class="small fw-semibold">Voir le programme <i class="bi bi-arrow-right"></i></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($partner['specialties'])): ?>
                    <div class="card shadow-sm p-4">
                        <h3 class="h5 mb-1">Spécialités proposées</h3>
                        <p class="text-muted small mb-3">Offres particulières de <?= e($partner['name']) ?>, en dehors du catalogue de filières HITAS.</p>
                        <ul class="mb-0">
                            <?php foreach (array_filter(array_map('trim', explode("\n", $partner['specialties']))) as $specialty): ?>
                                <li><?= e($specialty) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm p-4">
                        <h3 class="h5 mb-1">Filières proposées</h3>
                        <p class="text-muted small mb-0">Aucune filière renseignée pour ce partenaire pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm p-4">
                    <h3 class="h5 mb-3">Contact et accès</h3>
                    <p class="small text-muted">Cette page présente le partenaire et les programmes associés. Pour une inscription ou un renseignement, consultez l’équipe HITAS.</p>
                    <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-primary w-100">Contacter HITAS</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($partnerGallery): ?>
<section class="content-section py-5 reveal">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-eyebrow">Galerie du partenaire</span>
            <h2 class="section-title">Photos liées à <?= e($partner['name']) ?></h2>
            <p class="text-muted mx-auto" style="max-width:720px;">Découvrez des images illustrant la collaboration entre HITAS et ce partenaire.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($partnerGallery as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="gallery-card" <?= galleryLightboxAttrs($item, 'partner-gallery', $item['title'] ?: 'Photo partenaire', (string)$item['description']) ?>>
                        <div class="gallery-card-img" style="<?= !empty($item['image']) ? "background-image:url('" . UPLOAD_URL . "gallery/" . e($item['image']) . "')" : '' ?>"><?= galleryCardMediaHtml($item) ?></div>
                        <div class="gallery-card-content">
                            <h3 class="gallery-card-title"><?= e($item['title'] ?: 'Photo partenaire') ?></h3>
                            <?php if ($item['description']): ?>
                                <p class="gallery-card-text"><?= nl2br(e($item['description'])) ?></p>
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
