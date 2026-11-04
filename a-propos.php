<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
$stmt->execute(['a-propos']);
$page = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
$stmt->execute(['mission']);
$missionPage = $stmt->fetch();

// Photos des diaporamas (À propos, Notre mission) — gérées depuis
// /admin/page_galleries. Repli sur l'image unique historique (champ
// "image" de la page) si aucune photo n'a encore été ajoutée au diaporama.
function pageGalleryImages(PDO $pdo, string $key): array
{
    // Protégé : si la table "page_galleries" n'existe pas encore (migration
    // add_page_galleries.sql non exécutée), on ne fait pas planter toute la
    // page — on se contente de considérer qu'il n'y a pas de photo, et
    // l'affichage retombe sur l'ancien système (image unique / carte
    // décorative), exactement comme avant l'ajout de cette fonctionnalité.
    try {
        $stmt = $pdo->prepare('SELECT image FROM page_galleries WHERE page_key = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$key]);
        return array_column($stmt->fetchAll(), 'image');
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Affiche soit un diaporama (si plusieurs/une photo dans page_galleries),
 * soit l'image unique historique en repli, soit un bloc décoratif si rien
 * n'est encore configuré.
 */
function renderPageImageSlideshow(array $images, ?string $fallbackImage, string $altText, string $placeholderLabel): void
{
    if ($images) {
        echo '<div class="about-image-slideshow" data-duration="5000">';
        foreach ($images as $i => $img) {
            $active = $i === 0 ? ' is-active' : '';
            echo '<div class="about-image-slide' . $active . '" style="background-image:url(\'' . UPLOAD_URL . 'page_galleries/' . e($img) . '\')" aria-hidden="' . ($i === 0 ? 'false' : 'true') . '"></div>';
        }
        echo '</div>';
        if (count($images) > 1) {
            echo '<div class="about-image-dots">';
            foreach ($images as $i => $img) {
                echo '<span class="about-image-dot' . ($i === 0 ? ' is-active' : '') . '"></span>';
            }
            echo '</div>';
        }
    } elseif ($fallbackImage) {
        echo '<img src="' . UPLOAD_URL . 'pages/' . e($fallbackImage) . '" alt="' . e($altText) . '" class="about-image" loading="lazy">';
    } else {
        echo '<div class="about-image-placeholder"><div class="placeholder-inner"><span>' . e($placeholderLabel) . '</span></div></div>';
    }
}

$aboutGalleryImages = pageGalleryImages($pdo, 'a-propos');
$missionGalleryImages = pageGalleryImages($pdo, 'mission');

$gallery = $pdo->query('SELECT g.*, pr.title AS program_title, p.name AS partner_name
    FROM gallery g
    LEFT JOIN programs pr ON pr.id = g.program_id
    LEFT JOIN partners p ON p.id = g.partner_id
    ORDER BY g.sort_order ASC, g.created_at DESC')->fetchAll();
$galleryByCategory = [];
foreach ($gallery as $item) {
    $category = trim($item['category'] ?: 'Découverte');
    $galleryByCategory[$category][] = $item;
}

// Fiches campus (photo + mini description + détails), affichées juste sous
// "Notre mission". HITAS reste présenté dans son ensemble : cette table est
// indépendante de "campuses" (coordonnées/routage) et sert uniquement à
// mettre en avant un campus en particulier, géré depuis /admin/campus_highlights.
$campusHighlights = $pdo->query('SELECT * FROM campus_highlights WHERE is_published = 1 ORDER BY sort_order ASC, created_at ASC')->fetchAll();

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = mb_substr(trim($_POST['full_name'] ?? ''), 0, 120);
    $destination = mb_substr(trim($_POST['destination_country'] ?? ''), 0, 80);
    $program = mb_substr(trim($_POST['program'] ?? ''), 0, 150);
    $content = mb_substr(trim($_POST['content'] ?? ''), 0, 2000);

    // Anti-spam : piège à robots, jeton signé, délai minimal, 4 avis/heure/IP.
    $silentBot = false;
    $guardError = publicFormCheck('avis', 4, $silentBot);
    if ($guardError !== null) {
        $errors[] = $guardError;
    } elseif ($silentBot) {
        $message = 'Merci ! Votre avis a bien été envoyé. Il sera publié après validation.';
        $fullName = $destination = $program = $content = '';
    } else {
        if ($fullName === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($content === '') {
            $errors[] = 'Votre avis est obligatoire.';
        }
    }

    if (empty($errors) && !$silentBot) {
        $stmt = $pdo->prepare('INSERT INTO testimonials (full_name, destination_country, program, content, is_published) VALUES (?,?,?,?,0)');
        $stmt->execute([$fullName, $destination, $program, $content]);

        $message = 'Merci ! Votre avis a bien été envoyé. Il sera publié après validation.';
        $fullName = $destination = $program = $content = '';
    }
}

$testimonials = $pdo->query('SELECT * FROM testimonials WHERE is_published = 1 ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'À propos — HITAS';
$pageDescription = 'La mission de HITAS, la vie étudiante sur le campus, et les avis de nos anciens étudiants partis à l\'international.';
applySeoOverrides($page);
$currentPage = 'a-propos';
$pageBackgroundKey = 'a-propos';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb hitas-breadcrumb--light">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">À propos</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow">À propos</span>
                <h1 class="display-font mt-3">À propos de HITAS</h1>
                <p class="lead text-muted">Découvrez notre mission, la vie étudiante à HITAS et les témoignages de nos anciens étudiants.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
            <a class="btn btn-outline-primary btn-sm" href="#a-propos">À propos</a>
            <a class="btn btn-outline-primary btn-sm" href="#mission">Notre mission</a>
            <?php if ($campusHighlights): ?><a class="btn btn-outline-primary btn-sm" href="#nos-campus">Nos campus</a><?php endif; ?>
            <a class="btn btn-outline-primary btn-sm" href="#vie-etudiante">Vie étudiante</a>
            <a class="btn btn-outline-primary btn-sm" href="#avis-clients">Témoignages</a>
        </div>

        <div class="about-panel card shadow-sm p-4 mb-5" id="a-propos">
            <?php $aboutImage = $page['image'] ?? ''; ?>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="about-text">
                        <?php if ($page && $page['content']): ?>
                            <div class="page-content">
                                <?= sanitizeRichHtml($page['content']) ?>
                            </div>
                        <?php else: ?>
                            <p>HITAS est un établissement privé d'enseignement supérieur à Bali, Douala, rue Richard Bell, près du carrefour Kayo Elie.</p>
                            <p>Nous offrons aux étudiants motivés du Cameroun et d'Afrique centrale une formation alignée sur les standards des institutions étrangères.</p>
                            <p>L’ouverture de HITAS a été autorisée par une décision du ministre d’État en charge de l’Enseignement supérieur le 30 novembre 2021.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-wrapper">
                        <?php renderPageImageSlideshow($aboutGalleryImages, $aboutImage, 'Étudiants et campus HITAS', 'HITAS'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-panel card shadow-sm p-4 mb-5" id="mission">
            <?php $missionImage = $missionPage['image'] ?? ''; ?>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6 order-lg-2">
                    <div class="about-text">
                        <span class="section-eyebrow">Notre mission</span>
                        <?php if ($missionPage && $missionPage['content']): ?>
                            <div class="page-content mt-2">
                                <?= sanitizeRichHtml($missionPage['content']) ?>
                            </div>
                        <?php else: ?>
                            <h2 class="section-title mt-2">Former les talents de demain</h2>
                            <p>HITAS a pour mission de préparer des étudiants motivés à intégrer des institutions partenaires à l'étranger, avec un accompagnement académique et administratif de bout en bout.</p>
                            <p>Nous mettons un point d'honneur à offrir un encadrement rigoureux, ouvert sur l'international, pour donner à chaque étudiant les moyens de réussir son parcours et sa future carrière.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <div class="about-image-wrapper">
                        <?php renderPageImageSlideshow($missionGalleryImages, $missionImage, 'Notre mission - HITAS', 'Mission'); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($campusHighlights): ?>
        <div class="campuses-section mb-5" id="nos-campus">
            <div class="text-center mb-4">
                <span class="section-eyebrow">Nos campus</span>
                <h2 class="section-title">HITAS, au-delà de son campus phare de Douala</h2>
                <p class="text-muted mx-auto" style="max-width:720px;">HITAS s'appuie sur son campus phare de Douala, et sur d'autres implantations au service de nos étudiants.</p>
            </div>
            <div class="row g-4">
                <?php foreach ($campusHighlights as $highlight): ?>
                    <div class="col-md-6">
                        <div class="campus-card h-100">
                            <div class="campus-card-img">
                                <?php if (!empty($highlight['photo'])): ?>
                                    <img src="<?= UPLOAD_URL ?>campus_highlights/<?= e($highlight['photo']) ?>" alt="<?= e($highlight['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="campus-card-img-placeholder"><span><?= e($highlight['name']) ?></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="campus-card-body">
                                <?php if (!empty($highlight['title'])): ?>
                                    <div class="campus-card-name"><?= e($highlight['name']) ?></div>
                                    <h3 class="campus-card-title"><?= e($highlight['title']) ?></h3>
                                <?php else: ?>
                                    <h3 class="campus-card-title"><?= e($highlight['name']) ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($highlight['location'])): ?>
                                    <div class="campus-card-location"><i class="bi bi-geo-alt-fill"></i> <?= e($highlight['location']) ?></div>
                                <?php endif; ?>
                                <p class="campus-card-text"><?= e($highlight['short_description']) ?></p>
                                <?php if (!empty($highlight['full_description'])): ?>
                                    <?php $campusDetailId = 'campus-detail-' . (int)$highlight['id']; ?>
                                    <button class="campus-card-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($campusDetailId) ?>" aria-expanded="false" aria-controls="<?= e($campusDetailId) ?>">
                                        <span>Plus de détails</span> <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="collapse campus-card-detail" id="<?= e($campusDetailId) ?>">
                                        <p><?= nl2br(e($highlight['full_description'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="student-life-section mb-5" id="vie-etudiante">
            <div class="text-center mb-4">
                <span class="section-eyebrow">Vie étudiante</span>
                <h2 class="section-title">L’univers HITAS en images</h2>
                <p class="text-muted mx-auto" style="max-width:720px;">Découvrez nos événements, ateliers et moments de vie quotidienne à travers notre galerie de photos et de vidéos — cliquez sur une image pour l'agrandir et faire défiler la suite.</p>
            </div>

            <?php if (!$gallery): ?>
                <div class="alert alert-warning">Aucune photo publiée pour le moment.</div>
            <?php else: ?>
                <?php foreach ($galleryByCategory as $category => $items): ?>
                    <section class="gallery-category-section mb-5">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                            <div>
                                <span class="gallery-category-badge"><?= e($category) ?></span>
                                <h3 class="gallery-category-title">Découvrir <?= e($category) ?></h3>
                                <p class="text-muted gallery-category-copy">Plongez dans la vie quotidienne des étudiants HITAS autour de <?= strtolower(e($category)) ?>.</p>
                            </div>
                        </div>
                        <?php $galleryGroups = array_chunk($items, 8); ?>
                        <div class="gallery-slideshow" data-duration="6000">
                            <?php foreach ($galleryGroups as $gi => $group): ?>
                                <div class="gallery-slide<?= $gi === 0 ? ' is-active' : '' ?>">
                                    <div class="gallery-grid">
                                        <?php foreach ($group as $item): ?>
                                            <?php
                                            $lbSub = $item['description'] ?: ($item['program_title'] ? 'Filière : ' . $item['program_title'] : ($item['partner_name'] ? 'Partenaire : ' . $item['partner_name'] : ''));
                                            ?>
                                            <article class="gallery-card" <?= galleryLightboxAttrs($item, 'gal-' . substr(md5($category), 0, 8), $item['title'] ?: 'Image HITAS', (string)$lbSub) ?>>
                                                <div class="gallery-card-img" style="<?= !empty($item['image']) ? "background-image:url('" . UPLOAD_URL . "gallery/" . e($item['image']) . "')" : '' ?>">
                                                    <?= galleryCardMediaHtml($item) ?>
                                                    <div class="gallery-card-scrim"></div>
                                                    <span class="gallery-card-tag"><?= e($item['category'] ?: 'Vie étudiante') ?></span>
                                                    <div class="gallery-card-caption">
                                                        <h3 class="gallery-card-title"><?= e($item['title'] ?: 'Image HITAS') ?></h3>
                                                        <?php if ($item['program_title']): ?>
                                                            <span class="gallery-card-sub">Filière : <?= e($item['program_title']) ?></span>
                                                        <?php elseif ($item['partner_name']): ?>
                                                            <span class="gallery-card-sub">Partenaire : <?= e($item['partner_name']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($galleryGroups) > 1): ?>
                            <div class="gallery-dots">
                                <?php foreach ($galleryGroups as $gi => $group): ?>
                                    <span class="gallery-dot<?= $gi === 0 ? ' is-active' : '' ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="reviews-section" id="avis-clients">
            <div class="text-center mb-4">
                <span class="section-eyebrow">Témoignages</span>
                <h2 class="section-title">Ils ont choisi HITAS</h2>
                <p class="text-muted mx-auto" style="max-width:720px;">Découvrez les témoignages et partagez votre propre expérience.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success js-toast-alert"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger js-toast-alert"><ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>

            <?php if ($testimonials): ?>
                <div class="row g-4 mb-5">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="testimonial-card h-100">
                                <?php if (!empty($testimonial['video'])): ?>
                                    <video src="<?= UPLOAD_URL ?>testimonials/<?= e($testimonial['video']) ?>" <?= $testimonial['photo'] ? 'poster="' . UPLOAD_URL . 'testimonials/' . e($testimonial['photo']) . '"' : '' ?> class="rounded mb-3" style="height:180px; width:100%; object-fit:cover;" controls preload="metadata"></video>
                                <?php elseif ($testimonial['photo']): ?>
                                    <img src="<?= UPLOAD_URL ?>testimonials/<?= e($testimonial['photo']) ?>" alt="<?= e($testimonial['full_name']) ?>" class="img-fluid rounded mb-3" style="height:180px; object-fit:cover; width:100%;" loading="lazy">
                                <?php endif; ?>
                                <p class="mb-3"><?= nl2br(e($testimonial['content'])) ?></p>
                                <div class="fw-semibold"><?= e($testimonial['full_name']) ?></div>
                                <div class="small text-muted"><?= e($testimonial['program']) ?> <?= $testimonial['destination_country'] ? '· ' . e($testimonial['destination_country']) : '' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-5">Aucun avis publié pour le moment.</div>
            <?php endif; ?>

            <div class="card shadow-sm p-4">
                <h2 class="h4 mb-3">Déposez votre avis</h2>
                <p class="text-muted">Partagez votre expérience avec HITAS. Votre avis sera vérifié et publié après validation par notre équipe.</p>
                <form action="<?= BASE_URL ?>/a-propos.php" method="post">
                    <?= publicFormFields('avis') ?>
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($fullName ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pays de destination</label>
                            <input type="text" name="destination_country" class="form-control" value="<?= e($destination ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programme suivi</label>
                            <input type="text" name="program" class="form-control" value="<?= e($program ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Votre avis *</label>
                            <textarea name="content" class="form-control" rows="6" required><?= e($content ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-hitas-primary">Soumettre mon avis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php $activeCampus = getCurrentCampus(); $aboutAddress = $activeCampus['maps_query'] ?: $activeCampus['address'] ?: getSetting('address'); if ($aboutAddress): ?>
<section class="content-section py-5 reveal">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="text-center mb-4">
                    <div class="section-eyebrow">Nous trouver</div>
                    <h2 class="section-title">Localisation — <?= e($activeCampus['name']) ?></h2>
                </div>
                <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm">
                    <iframe src="<?= e(getGoogleMapsEmbedUrl($aboutAddress)) ?>" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="text-center mt-3">
                    <a href="<?= e(getGoogleMapsUrl($aboutAddress)) ?>" target="_blank" rel="noopener" class="fw-semibold"><i class="bi bi-geo-alt me-1"></i> Ouvrir dans Google Maps</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/site_footer.php';
