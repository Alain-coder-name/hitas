<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$countries = $pdo->query('SELECT DISTINCT country FROM partners WHERE is_published = 1 ORDER BY country')->fetchAll(PDO::FETCH_COLUMN);
$selectedCountry = trim($_GET['country'] ?? '');

$partnerSql = 'SELECT * FROM partners WHERE is_published = 1';
$params = [];
if ($selectedCountry !== '' && in_array($selectedCountry, $countries, true)) {
    $partnerSql .= ' AND country = ?';
    $params[] = $selectedCountry;
}
$partnerSql .= ' ORDER BY sort_order ASC, id ASC';
$stmt = $pdo->prepare($partnerSql);
$stmt->execute($params);
$partners = $stmt->fetchAll();

// Nombre de filières par partenaire, affiché sur chaque carte (le détail
// complet des filières + la biographie se trouvent sur la fiche "En savoir
// plus" — voir partenaire.php).
$programCounts = $pdo->query('SELECT partner_id, COUNT(*) AS cnt FROM program_partners GROUP BY partner_id')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Institutions partenaires — HITAS';
$pageDescription = 'Découvrez les institutions et établissements partenaires de HITAS à travers le monde, et les filières qui y mènent.';
applySeoOverrides(getPageSeo('partenaires'));
$currentPage = 'partenaires';
$pageBackgroundKey = 'partenaires';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Partenaires</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow text-white"><?= e(getSetting('partenaires_hero_eyebrow', 'Partenaires')) ?></span>
                <h1 class="display-font mt-3"><?= e(getSetting('partenaires_hero_title', 'Institutions partenaires')) ?></h1>
                <p class="lead text-white-75"><?= e(getSetting('partenaires_hero_lead', 'Découvrez les établissements qui accueillent nos diplômés et les destinations de nos filières.')) ?></p>
                <p class="text-white-75 small mb-0">Vous cherchez une filière précise ? <a href="<?= BASE_URL ?>/programmes.php#programFinderForm" class="text-white text-decoration-underline">Utilisez la recherche de filière</a>, elle vous montrera directement les institutions partenaires qui la proposent.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <div class="mb-5">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0" for="country">Filtrer par pays</label>
                </div>
                <div class="col-auto">
                    <select name="country" id="country" class="form-select">
                        <option value="">Tous les pays</option>
                        <?php foreach ($countries as $countryOption): ?>
                            <option value="<?= e($countryOption) ?>" <?= $countryOption === $selectedCountry ? 'selected' : '' ?>><?= e($countryOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                </div>
                <?php if ($selectedCountry !== ''): ?>
                    <div class="col-auto">
                        <a href="<?= BASE_URL ?>/partenaires.php" class="btn btn-outline-secondary">Effacer le filtre</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!$partners): ?>
            <div class="alert alert-warning">Aucun partenaire publié pour le moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($partners as $partner): ?>
                    <?php
                    $cnt = (int)($programCounts[$partner['id']] ?? 0);
                    $shortDesc = $partner['description'] ? mb_strimwidth(strip_tags($partner['description']), 0, 160, '…') : 'Partenaires de HITAS';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="partner-list-card h-100">
                            <div class="partner-list-card-top">
                                <?php if ($partner['logo']): ?>
                                    <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="partner-list-card-logo" loading="lazy">
                                <?php else: ?>
                                    <div class="partner-list-card-logo partner-list-card-logo--placeholder"><i class="bi bi-bank"></i></div>
                                <?php endif; ?>
                                <span class="partner-list-card-country"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></span>
                            </div>
                            <h3 class="h5 mb-2"><?= e($partner['name']) ?></h3>
                            <?php if (!empty($partner['degree_level'])): ?><span class="badge bg-hitas-gold-badge mb-2"><?= e($partner['degree_level']) ?></span><?php endif; ?>
                            <p class="text-muted small mb-3"><?= e($shortDesc) ?></p>
                            <?php if ($cnt > 0): ?>
                                <div class="small fw-semibold text-muted mb-3"><?= $cnt ?> filière<?= $cnt > 1 ? 's' : '' ?> proposée<?= $cnt > 1 ? 's' : '' ?></div>
                            <?php elseif (!empty($partner['specialties'])): ?>
                                <div class="small fw-semibold text-muted mb-3"><?= nl2br(e(mb_strimwidth($partner['specialties'], 0, 120, '…'))) ?></div>
                            <?php endif; ?>
                            <div class="mt-auto partner-list-card-actions">
                                <a href="<?= BASE_URL ?>/partenaire.php?id=<?= (int)$partner['id'] ?>" class="btn btn-hitas-primary btn-sm w-100">En savoir plus <i class="bi bi-arrow-right"></i></a>
                                <?php if ($partner['website']): ?>
                                    <a href="<?= e($partner['website']) ?>" target="_blank" rel="noopener noreferrer" class="partner-list-card-website">
                                        <i class="bi bi-box-arrow-up-right"></i> Site officiel du partenaire
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/site_footer.php';
