<?php
require_once __DIR__ . '/includes/site_helpers.php';

$slug = $_GET['slug'] ?? '';
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM programs WHERE slug = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$slug]);
$program = $stmt->fetch();

if (!$program) {
    header('HTTP/1.0 404 Not Found');
    die('Programme introuvable.');
}

$partnerStmt = $pdo->prepare('SELECT p.* FROM partners p JOIN program_partners pp ON pp.partner_id = p.id WHERE pp.program_id = ? AND p.is_published = 1 ORDER BY p.name');
$partnerStmt->execute([$program['id']]);
$partners = $partnerStmt->fetchAll();

$galleryStmt = $pdo->prepare('SELECT * FROM gallery WHERE program_id = ? ORDER BY created_at DESC');
$galleryStmt->execute([$program['id']]);
$programGallery = $galleryStmt->fetchAll();

// --- "Trouvez votre filière" : recherche parmi les programmes proposés par
// les universités partenaires, par filière + pays (optionnel). Reste sur la
// même page (paramètres GET), aucune dépendance JS.
$finderPrograms = $pdo->query('SELECT id, title FROM programs WHERE is_published = 1 ORDER BY title')->fetchAll();
$finderCountries = $pdo->query("SELECT DISTINCT country FROM partners WHERE is_published = 1 AND country IS NOT NULL AND country <> '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

$finderSubmitted = isset($_GET['filiere_id']) && $_GET['filiere_id'] !== '';
$finderProgramId = $finderSubmitted ? (int)$_GET['filiere_id'] : 0;
$finderCountry = trim($_GET['pays'] ?? '');
$finderProgram = null;
$finderResults = [];
$finderResultsAnyCountry = [];

if ($finderSubmitted && $finderProgramId) {
    $stmt = $pdo->prepare('SELECT * FROM programs WHERE id = ? AND is_published = 1');
    $stmt->execute([$finderProgramId]);
    $finderProgram = $stmt->fetch();

    if ($finderProgram) {
        $sql = 'SELECT p.* FROM partners p
                JOIN program_partners pp ON pp.partner_id = p.id
                WHERE pp.program_id = ? AND p.is_published = 1';
        $params = [$finderProgramId];
        if ($finderCountry !== '') {
            $sql .= ' AND p.country = ?';
            $params[] = $finderCountry;
        }
        $sql .= ' ORDER BY p.name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $finderResults = $stmt->fetchAll();

        // Si aucun résultat dans le pays choisi, on propose intelligemment
        // les autres pays où cette filière est disponible chez nos partenaires,
        // plutôt que de laisser l'utilisateur devant une impasse.
        if (!$finderResults && $finderCountry !== '') {
            $stmt = $pdo->prepare('SELECT p.* FROM partners p
                JOIN program_partners pp ON pp.partner_id = p.id
                WHERE pp.program_id = ? AND p.is_published = 1
                ORDER BY p.name');
            $stmt->execute([$finderProgramId]);
            $finderResultsAnyCountry = $stmt->fetchAll();
        }
    }
}

$pageTitle = $program['title'] . ' — HITAS';
$pageDescription = $program['short_description'] ?: ('Filière ' . $program['title'] . ' à HITAS, avec passerelle vers une institution partenaire à l\'étranger.');
applySeoOverrides($program, 'programs');
$currentPage = 'programmes';
$pageBackgroundKey = 'programmes';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/programmes.php">Trouver ma filière</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($program['title']) ?></li>
            </ol>
        </nav>
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="eyebrow text-white">Programme</span>
                <h1 class="display-font mt-3"><?= e($program['title']) ?></h1>
                <p class="lead text-white-75"><?= e($program['short_description'] ?: 'Description de programme en cours de rédaction.') ?></p>
                <div class="mt-4">
                    <?php if (!empty($program['degree_level'])): ?><span class="badge bg-hitas-gold-badge me-2"><?= e($program['degree_level']) ?></span><?php endif; ?>
                    <?php if (!empty($program['category'])): ?><span class="badge bg-light text-dark me-2"><?= e($program['category']) ?></span><?php endif; ?>
                    <?php if ($program['prerequisites']): ?><span class="badge bg-light text-dark me-2">Prérequis disponibles</span><?php endif; ?>
                    <?php if (!empty($program['outcomes'])): ?><span class="badge bg-light text-dark">Débouchés renseignés</span><?php endif; ?>
                </div>
            </div>
            <?php if ($program['image']): ?>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="detail-image" style="background-image:url('<?= UPLOAD_URL ?>programs/<?= e($program['image']) ?>')"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="program-detail-card mb-4">
                    <span class="program-detail-eyebrow">Présentation</span>
                    <h2 class="program-detail-heading">Ce que propose HITAS avec « <?= e($program['title']) ?> »</h2>
                    <div class="program-detail-body">
                        <?= nl2br(e($program['description'] ?: 'Le descriptif complet de ce programme sera ajouté prochainement.')) ?>
                    </div>
                    <?php if ($program['prerequisites']): ?>
                        <div class="program-detail-subblock">
                            <h3><i class="bi bi-clipboard-check"></i> Prérequis</h3>
                            <div class="program-detail-body">
                                <?= nl2br(e($program['prerequisites'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($program['outcomes'])): ?>
                        <div class="program-detail-subblock">
                            <h3><i class="bi bi-briefcase"></i> Débouchés</h3>
                            <div class="program-detail-body">
                                <?= nl2br(e($program['outcomes'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($partners): ?>
                    <div class="card shadow-sm p-4">
                        <h3 class="h5 mb-3">Institutions partenaires</h3>
                        <div class="row g-3">
                            <?php foreach ($partners as $partner): ?>
                                <div class="col-sm-6">
                                    <div class="partner-card p-3 h-100">
                                        <?php if ($partner['logo']): ?>
                                            <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="mb-3 partner-logo" loading="lazy">
                                        <?php endif; ?>
                                        <h4 class="h6 mb-1"><?= e($partner['name']) ?></h4>
                                        <div class="text-muted mb-2"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></div>
                                        <p class="small mb-0"><?= e($partner['description'] ?: 'Partenaire de HITAS pour les échanges et doubles diplômes.') ?></p>
                                        <?php if ($partner['website']): ?>
                                            <div class="mt-2"><a href="<?= e($partner['website']) ?>" target="_blank" rel="noopener" class="small">Visiter le site partenaire</a></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm p-4">
                    <h3 class="h5 mb-3">Contactez-nous</h3>
                    <p class="small text-muted">Notre équipe peut vous renseigner sur les conditions d'admission et les institutions partenaires.</p>
                    <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-primary w-100">Envoyer une demande</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($programGallery): ?>
<section class="content-section py-5">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-eyebrow">Galerie du programme</span>
            <h2 class="section-title">Photos liées au programme <?= e($program['title']) ?></h2>
            <p class="text-muted mx-auto" style="max-width:720px;">Découvrez les images des activités et de l’expérience vécue au sein de ce programme.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($programGallery as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="gallery-card" <?= galleryLightboxAttrs($item, 'program-gallery', $item['title'] ?: 'Photo du programme', (string)$item['description']) ?>>
                        <div class="gallery-card-img" style="<?= !empty($item['image']) ? "background-image:url('" . UPLOAD_URL . "gallery/" . e($item['image']) . "')" : '' ?>"><?= galleryCardMediaHtml($item) ?></div>
                        <div class="gallery-card-content">
                            <h3 class="gallery-card-title"><?= e($item['title'] ?: 'Photo du programme') ?></h3>
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

<section class="content-section py-5 reveal program-finder-section">
    <div class="container">
        <div class="program-finder-card">
            <div class="text-center mb-4">
                <span class="section-eyebrow">Pas encore fixé sur cette filière ?</span>
                <h2 class="section-title">Trouvez la filière et l'institution faites pour vous</h2>
                <p class="text-muted mx-auto" style="max-width:640px;">Indiquez la filière qui vous intéresse, et si vous le souhaitez un pays de destination : nous recherchons pour vous parmi toutes nos institutions partenaires.</p>
            </div>
            <form action="<?= BASE_URL ?>/programme.php" method="get" class="program-finder-form">
                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                <div class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-5">
                        <label class="form-label" for="finderFiliere">Quelle filière vous intéresse ?</label>
                        <select name="filiere_id" id="finderFiliere" class="form-select" required>
                            <option value="">Choisir une filière</option>
                            <?php foreach ($finderPrograms as $fp): ?>
                                <option value="<?= (int)$fp['id'] ?>" <?= $finderProgramId === (int)$fp['id'] ? 'selected' : '' ?>><?= e($fp['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="finderPays">Pays souhaité <span class="text-muted fw-normal">(facultatif)</span></label>
                        <select name="pays" id="finderPays" class="form-select">
                            <option value="">Tous les pays</option>
                            <?php foreach ($finderCountries as $fc): ?>
                                <option value="<?= e($fc) ?>" <?= $finderCountry === $fc ? 'selected' : '' ?>><?= e($fc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-hitas-primary w-100"><i class="bi bi-search"></i> Rechercher</button>
                    </div>
                </div>
            </form>

            <?php if ($finderSubmitted): ?>
                <div class="program-finder-results">
                    <?php if (!$finderProgram): ?>
                        <p class="text-muted text-center mb-0">Merci de choisir une filière valide dans la liste.</p>
                    <?php elseif ($finderResults): ?>
                        <h3 class="program-finder-results-title">
                            <?= count($finderResults) ?> institution<?= count($finderResults) > 1 ? 's' : '' ?> partenaire<?= count($finderResults) > 1 ? 's' : '' ?> propose<?= count($finderResults) > 1 ? 'nt' : '' ?>
                            « <?= e($finderProgram['title']) ?> »<?= $finderCountry !== '' ? ' en ' . e($finderCountry) : '' ?>
                        </h3>
                        <p class="text-center mb-3"><a href="<?= BASE_URL ?>/programme.php?slug=<?= urlencode($finderProgram['slug']) ?>" class="small fw-semibold">Voir la fiche de la filière « <?= e($finderProgram['title']) ?> » <i class="bi bi-arrow-right"></i></a></p>
                        <div class="row g-3 mt-1">
                            <?php foreach ($finderResults as $partner): ?>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="partner-card p-3 h-100">
                                        <?php if ($partner['logo']): ?>
                                            <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="mb-3 partner-logo" loading="lazy">
                                        <?php endif; ?>
                                        <h4 class="h6 mb-1"><?= e($partner['name']) ?></h4>
                                        <div class="text-muted mb-2"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></div>
                                        <?php if ($partner['website']): ?>
                                            <a href="<?= e($partner['website']) ?>" target="_blank" rel="noopener" class="small fw-semibold">Visiter le site partenaire <i class="bi bi-arrow-right"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($finderResultsAnyCountry): ?>
                        <p class="text-center text-muted mb-3">Cette filière n'est pas proposée par nos partenaires en <?= e($finderCountry) ?> pour le moment — mais elle l'est ailleurs :</p>
                        <div class="row g-3">
                            <?php foreach ($finderResultsAnyCountry as $partner): ?>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="partner-card p-3 h-100">
                                        <?php if ($partner['logo']): ?>
                                            <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="mb-3 partner-logo" loading="lazy">
                                        <?php endif; ?>
                                        <h4 class="h6 mb-1"><?= e($partner['name']) ?></h4>
                                        <div class="text-muted mb-2"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="text-muted mb-3">Aucune institution partenaire ne propose actuellement cette filière. Notre équipe peut vous orienter vers d'autres options.</p>
                            <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-outline">Nous contacter</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site_footer.php';
