<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();

$programCount = (int)$pdo->query('SELECT COUNT(*) FROM programs WHERE is_published=1')->fetchColumn();
$partnerCount = (int)$pdo->query('SELECT COUNT(*) FROM partners WHERE is_published=1')->fetchColumn();
$countryCount = (int)$pdo->query('SELECT COUNT(DISTINCT country) FROM partners WHERE is_published=1')->fetchColumn();

// Partenaire adossé à la co-diplomation HITAS (Université de Ngaoundéré) : le
// bloc "Diplôme ou co-diplômation" du schéma de parcours (ci-dessous) pointe
// directement vers sa fiche, puisque c'est via ce partenaire que HITAS délivre
// ses diplômes/co-diplômations.
$codiplomationPartner = $pdo->query("SELECT id FROM partners WHERE is_published=1 AND co_diplomation_note IS NOT NULL AND co_diplomation_note <> '' ORDER BY id LIMIT 1")->fetch();

// "Filières phares" : sélection éditoriale (cochée depuis /admin/programs),
// pas la liste complète des programmes. Repli sur tous les programmes publiés
// si aucune n'a encore été marquée comme phare, pour ne pas laisser le bloc vide.
$featuredPrograms = $pdo->query("SELECT * FROM programs WHERE is_published=1 AND is_featured=1 ORDER BY featured_order ASC, created_at DESC")->fetchAll();

// Université partenaire de chaque filière (nom + logo), pour que le visiteur
// voie tout de suite vers quel partenaire mène chaque filière phare —
// et pour renvoyer directement vers ce partenaire (page précise si connue,
// sinon son site officiel, sinon notre fiche interne en dernier recours).
$programPartnerMap = [];
foreach ($pdo->query("SELECT pp.program_id, pp.partner_url, p.id AS partner_id, p.name AS partner_name, p.logo AS partner_logo, p.website AS partner_website
        FROM program_partners pp
        JOIN partners p ON p.id = pp.partner_id
        WHERE p.is_published = 1
        ORDER BY p.name") as $ppRow) {
    if (!isset($programPartnerMap[$ppRow['program_id']])) {
        $programPartnerMap[$ppRow['program_id']] = $ppRow;
    }
}
function homeFiliereLinkAttrs(array $programPartnerMap, int $programId, string $fallbackSlug): array
{
    $row = $programPartnerMap[$programId] ?? null;
    $direct = trim((string)($row['partner_url'] ?? ''));
    if ($direct !== '') {
        return ['href' => $direct, 'external' => true];
    }
    $partnerWebsite = trim((string)($row['partner_website'] ?? ''));
    if ($partnerWebsite !== '') {
        return ['href' => $partnerWebsite, 'external' => true];
    }
    return ['href' => BASE_URL . '/programme.php?slug=' . urlencode($fallbackSlug), 'external' => false];
}

if (!$featuredPrograms) {
    $featuredPrograms = $pdo->query('SELECT * FROM programs WHERE is_published=1 ORDER BY created_at DESC')->fetchAll();
}
$partners = $pdo->query('SELECT * FROM partners WHERE is_published=1 ORDER BY RAND() LIMIT 8')->fetchAll();
$latestNews = $pdo->query('SELECT * FROM news WHERE is_published=1 ORDER BY published_at DESC LIMIT 10')->fetchAll();
$testimonials = $pdo->query('SELECT * FROM testimonials WHERE is_published=1 ORDER BY RAND() LIMIT 3')->fetchAll();
$heroSlides = $pdo->query('SELECT * FROM hero_slides WHERE is_published=1 ORDER BY sort_order ASC, created_at ASC')->fetchAll();
// Alimente désormais la bande défilante sous le hero (géré depuis /admin/stats),
// et non plus l'ancien bandeau "stats-bar" (retiré).
$stats = $pdo->query('SELECT * FROM stats WHERE is_active=1 ORDER BY sort_order ASC, id ASC')->fetchAll();

// Blocs de l'accueil : ordre et activation pilotés depuis /admin/home_sections
$homeSections = $pdo->query('SELECT section_key FROM home_sections WHERE is_active=1 ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
if (!$homeSections) {
    // Base non migrée / table vide : on garde l'ordre historique par défaut
    // (le bloc "stats" a été retiré : ses chiffres sont déjà repris par la
    // bande défilante juste sous le hero)
    $homeSections = ['hero', 'programs', 'partners', 'pillars', 'news', 'testimonials', 'cta'];
}

$pageTitle = getSetting('site_name', 'HITAS') . ' — Votre passerelle vers les institutions du monde';
$pageDescription = "HITAS prépare les bacheliers puis les accompagne vers l'une de ses institutions partenaires à l'étranger, selon leur filière.";
$currentPage = 'accueil';
// Filigrane du logo HITAS en arrière-plan, comme sur les autres pages
// (voir includes/site_helpers.php > pageBackgroundDefs()).
$pageBackgroundKey = 'accueil';
applySeoOverrides(getPageSeo('accueil'));
include __DIR__ . '/includes/site_header.php';

/* ==========================================================
   Chaque fonction rend un bloc de la page d'accueil.
   L'ordre et l'activation sont pilotés depuis l'admin
   (module « Sections & blocs »).
   ========================================================== */

function renderHomeSection_hero(array $heroSlides, array $latestNews, array $partners = [], ?int $codiplomationPartnerId = null): void
{
    ?>
    <section class="hero<?= $heroSlides ? ' hero--has-bg' : '' ?>">
        <?php if ($heroSlides): ?>
            <div class="hero-slideshow" aria-hidden="true">
                <?php foreach ($heroSlides as $i => $slide): ?>
                    <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
                         data-duration="<?= (int)($slide['duration_ms'] ?: 6000) ?>"
                         data-title="<?= e($slide['title'] ?? '') ?>"
                         data-subtitle="<?= e($slide['subtitle'] ?? '') ?>"
                         data-btn-text="<?= e($slide['button_text'] ?? '') ?>"
                         data-btn-link="<?= e($slide['button_link'] ? siteUrl($slide['button_link']) : '') ?>"
                         <?= empty($slide['video']) ? "style=\"background-image:url('" . UPLOAD_URL . 'hero/' . e($slide['image']) . "')\"" : '' ?>>
                        <?php if (!empty($slide['video'])): ?>
                            <video class="hero-slide-video" src="<?= UPLOAD_URL ?>hero/<?= e($slide['video']) ?>" autoplay muted loop playsinline></video>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="hero-overlay"></div>
        <?php endif; ?>

        <?php if ($latestNews): ?>
        <!-- Cluster de 6 bulles flottantes (façon bulles de savon) niché dans
             l'angle supérieur droit du hero. Chacune grandit à son tour pour
             révéler une actualité/publicité (photo, titre, "En savoir
             plus"), puis rétrécit pour laisser la suivante prendre le relais.
             S'il y a plus de 6 actualités, la rotation continue de défiler
             parmi toutes (voir hero-bubbles.js plus bas) : les 6 bulles ne
             sont que des emplacements tournants, pas une limite au contenu. -->
        <div class="hero-bubbles" data-news='<?= e(json_encode(array_map(static function ($n) {
            return [
                'title' => $n['title'],
                'image' => $n['image'] ? UPLOAD_URL . 'news/' . $n['image'] : null,
                'video' => $n['video'] ? UPLOAD_URL . 'news/' . $n['video'] : null,
                'url' => newsTargetUrl($n),
                'blank' => (bool) ($n['is_advertisement'] && !empty($n['external_link'])),
            ];
        }, $latestNews))) ?>' data-duration="4500">
            <?php for ($b = 0; $b < 6; $b++): ?>
                <div class="hero-bubble hero-bubble--<?= $b + 1 ?>" data-slot="<?= $b ?>">
                    <div class="hero-bubble-shape"></div>
                    <div class="hero-bubble-content">
                        <div class="hero-bubble-photo"></div>
                        <div class="hero-bubble-tint"></div>
                        <div class="hero-bubble-text">
                            <span class="hero-bubble-eyebrow"><i class="bi bi-broadcast"></i> Actualités HITAS</span>
                            <h3 class="hero-bubble-title"></h3>
                            <a href="#" class="hero-bubble-link" target="_self">En savoir plus <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>

            <!-- Bouton "Rejoindre notre chaîne HITAS TV" — placé dans l'espace
                 vide sous le cluster de bulles, suit le même repère de
                 positionnement qu'elles. -->
            <?php $hitasTvUrl = trim((string) getSetting('hitas_tv_url')); if ($hitasTvUrl !== ''): ?>
                <a href="<?= e($hitasTvUrl) ?>" target="_blank" rel="noopener noreferrer" class="hero-tv-btn">
                    <i class="bi bi-play-circle-fill"></i> Rejoindre notre chaîne HITAS TV
                </a>
            <?php endif; ?>

            <!-- Bouton "Rejoindre notre chaîne WhatsApp" — permanent, en
                 plus (pas à la place) de la popup qui apparaît 6 secondes
                 après l'ouverture du site (voir includes/site_footer.php,
                 inchangée). Reste affiché en continu, sous le bouton
                 HITAS TV. -->
            <?php $waChannelBtnUrl = trim((string) getSetting('whatsapp_channel_url')); if ($waChannelBtnUrl !== ''): ?>
                <a href="<?= e($waChannelBtnUrl) ?>" target="_blank" rel="noopener noreferrer" class="hero-tv-btn hero-wa-btn">
                    <i class="bi bi-whatsapp"></i> Rejoindre notre chaîne WhatsApp
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-<?= $latestNews ? '7' : '12' ?>">
                    <?php
                    $hero_eyebrow = getSetting('home_hero_eyebrow', 'Hanseatic Institute of Technology and Applied Sciences');
                    $hero_title = getSetting('home_hero_title', "Une à deux années pour préparer votre départ vers une institution partenaire, à l'étranger.");
                    $hero_lead = getSetting('home_hero_lead', 'HITAS forme les bacheliers dans un cadre exigeant, puis les accompagne vers l\'une de nos institutions partenaires selon leur filière — la passerelle entre votre diplôme et votre avenir académique international.');
                    $hero_cta_text = getSetting('home_hero_cta_text', 'Découvrir les programmes de nos institutions partenaires');
                    $hero_cta_link = getSetting('home_hero_cta_link', '/programmes.php');
                    ?>
                    <div class="eyebrow mb-2" data-hero-eyebrow translate="no"><?= e($hero_eyebrow) ?></div>
                    <h1 class="display-font mb-3" data-hero-title><?= e($hero_title) ?></h1>
                    <p class="lead" data-hero-lead><?= e($hero_lead) ?></p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="<?= e(siteUrl($hero_cta_link)) ?>" class="btn btn-hitas-primary btn-lg px-4" data-hero-cta><?= e($hero_cta_text) ?></a>
                        <a href="#footer-phone-contact" class="btn btn-hitas-outline btn-lg px-4">Nous contacter</a>
                    </div>

                    <!-- Signature : le parcours de l'étudiant -->
                    <div class="journey-tree">
                        <div class="journey-tree-root">
                            <div class="node"><i class="bi bi-mortarboard"></i></div>
                            <div class="label">BAC / GCE en poche</div>
                        </div>
                        <div class="journey-tree-branches">
                            <div class="journey-tree-branch">
                                <i class="bi bi-arrow-right journey-tree-connector"></i>
                                <div class="journey-tree-step">
                                    <div class="label">Programme d'étude à HITAS</div>
                                </div>
                                <i class="bi bi-arrow-right"></i>
                                <div class="journey-tree-step is-final<?= $codiplomationPartnerId ? ' journey-tree-step--featured' : '' ?>">
                                    <?php if ($codiplomationPartnerId): ?>
                                        <a class="label journey-tree-link" href="<?= BASE_URL ?>/partenaire.php?id=<?= (int)$codiplomationPartnerId ?>">Diplôme ou co-diplômation</a>
                                    <?php else: ?>
                                        <div class="label">Diplôme ou co-diplômation</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="journey-tree-branch">
                                <i class="bi bi-arrow-right journey-tree-connector"></i>
                                <div class="journey-tree-step">
                                    <div class="label">Programme préparatoire</div>
                                </div>
                                <i class="bi bi-arrow-right"></i>
                                <div class="journey-tree-step is-final">
                                    <a class="label journey-tree-link" href="<?= BASE_URL ?>/partenaires.php">Institutions partenaires (à l'étranger)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function renderHomeSection_programs(array $featuredPrograms, array $programPartnerMap = []): void
{
    ?>
    <section class="py-5 mt-4 reveal">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <div class="section-eyebrow">Nos formations</div>
                    <h2 class="section-title mb-0">Filières phares</h2>
                </div>
                <a href="<?= BASE_URL ?>/programmes.php#filieres-phares" class="d-none d-md-inline-block">Voir toutes les filières phares <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if (!$featuredPrograms): ?>
                <p class="text-muted">Nos programmes seront bientôt disponibles ici.</p>
            <?php else: ?>
            <?php $programGroups = array_chunk($featuredPrograms, 3); ?>
            <div class="pillars-slideshow" data-duration="6000">
                <?php foreach ($programGroups as $gi => $group): ?>
                    <div class="pillars-slide<?= $gi === 0 ? ' is-active' : '' ?>">
                        <div class="row g-4">
                            <?php foreach ($group as $p): ?>
                                <?php
                                $partnerRow = $programPartnerMap[$p['id']] ?? null;
                                $flink = homeFiliereLinkAttrs($programPartnerMap, (int)$p['id'], $p['slug']);
                                ?>
                                <div class="col-md-4 program-card-cell">
                                    <div class="program-card">
                                        <div class="program-img" style="<?= $p['image'] ? "background-image:url('" . UPLOAD_URL . "programs/" . e($p['image']) . "')" : '' ?>"></div>
                                        <div class="p-3">
                                            <?php if ($partnerRow && !empty($partnerRow['partner_name'])): ?>
                                                <div class="program-card-partner">
                                                    <?php if (!empty($partnerRow['partner_logo'])): ?>
                                                        <img src="<?= UPLOAD_URL ?>partners/<?= e($partnerRow['partner_logo']) ?>" alt="<?= e($partnerRow['partner_name']) ?>" class="program-card-partner-logo" loading="lazy">
                                                    <?php endif; ?>
                                                    <span class="program-card-partner-name"><?= e($partnerRow['partner_name']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <h5 class="mb-2"><?= e($p['title']) ?></h5>
                                            <p class="small text-muted mb-3"><?= e($p['short_description']) ?></p>
                                            <a href="<?= e($flink['href']) ?>" class="small fw-semibold"<?= $flink['external'] ? ' target="_blank" rel="noopener"' : '' ?>>En savoir plus <i class="bi <?= $flink['external'] ? 'bi-box-arrow-up-right' : 'bi-arrow-right' ?>"></i></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($programGroups) > 1): ?>
                <div class="pillars-dots">
                    <?php foreach ($programGroups as $gi => $group): ?>
                        <span class="pillars-dot<?= $gi === 0 ? ' is-active' : '' ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function renderHomeSection_partners(array $partners): void
{
    ?>
    <section class="py-5 reveal" style="background: var(--hitas-ivory);">
        <div class="container">
            <div class="text-center mb-4">
                <div class="section-eyebrow">Ils nous font confiance</div>
                <h2 class="section-title">Institutions & établissements partenaires</h2>
            </div>
            <?php if (!$partners): ?>
                <p class="text-muted text-center mb-0">Nos partenaires seront bientôt affichés ici.</p>
            <?php else: ?>
            <div class="partner-marquee">
                <div class="partner-marquee-track">
                    <?php foreach (array_merge($partners, $partners) as $partner): ?>
                        <?php if ($partner['logo']): ?>
                            <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" title="<?= e($partner['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="fw-semibold text-muted"><?= e($partner['name']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/partenaires.php" class="fw-semibold">Voir tous nos partenaires <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function renderHomeTickerBanner(array $stats, int $programCount, int $partnerCount, int $countryCount): void
{
    // Bande défilante fine, juste sous la section Accueil : le contenu est
    // désormais piloté depuis /admin/stats (libellé, valeur, icône, ordre,
    // visibilité — l'ancien module "Chiffres clés" de l'ex stats-bar).
    // Si rien n'est configuré côté admin, on retombe sur les 3 compteurs
    // réels (programmes / partenaires / pays), calculés plus haut.
    $tickerItems = [];
    foreach ($stats as $s) {
        if (trim((string)$s['value']) === '') {
            continue;
        }
        $tickerItems[] = [
            'icon'  => $s['icon'] ?: 'bi-graph-up-arrow',
            'value' => $s['value'],
            'label' => $s['label'],
        ];
    }
    if (!$tickerItems) {
        $tickerItems = [
            ['icon' => 'bi-mortarboard', 'value' => $programCount . '+', 'label' => 'Filières'],
            ['icon' => 'bi-bank',        'value' => $partnerCount . '+', 'label' => 'Institutions partenaires'],
            ['icon' => 'bi-globe2',      'value' => $countryCount . '+', 'label' => 'Pays de destination'],
        ];
    }
    if (!$tickerItems) {
        return;
    }
    ?>
    <div class="home-ticker-banner" aria-hidden="true">
        <div class="home-ticker-track">
            <?php foreach (array_merge($tickerItems, $tickerItems, $tickerItems, $tickerItems) as $item): ?>
                <span class="home-ticker-item">
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <span class="home-ticker-value"><?= e($item['value']) ?></span>
                    <span class="home-ticker-name"><?= e($item['label']) ?></span>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderHomeSection_pillars(): void
{
    $p1t = getSetting('home_pillar_1_title'); $p1 = getSetting('home_pillar_1_text');
    $p2t = getSetting('home_pillar_2_title'); $p2 = getSetting('home_pillar_2_text');
    $p3t = getSetting('home_pillar_3_title'); $p3 = getSetting('home_pillar_3_text');
    if (!($p1t || $p1 || $p2t || $p2 || $p3t || $p3)) {
        return;
    }
    ?>
    <section class="py-5 reveal">
        <div class="container">
            <div class="text-center mb-4">
                <div class="section-eyebrow">Pourquoi HITAS ?</div>
                <h2 class="section-title">Nos trois piliers</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="pillar-card text-center h-100">
                        <span class="pillar-num">01</span>
                        <div class="pillar-card-inner p-4">
                            <div class="pillar-icon"><i class="bi bi-mortarboard-fill"></i></div>
                            <h4 class="h5 mb-2"><?= e($p1t ?: 'Exigence académique') ?></h4>
                            <p class="small text-muted mb-0"><?= e($p1 ?: 'Une formation rigoureuse préparant aux standards internationaux.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pillar-card text-center h-100">
                        <span class="pillar-num">02</span>
                        <div class="pillar-card-inner p-4">
                            <div class="pillar-icon"><i class="bi bi-people-fill"></i></div>
                            <h4 class="h5 mb-2"><?= e($p2t ?: 'Accompagnement personnel') ?></h4>
                            <p class="small text-muted mb-0"><?= e($p2 ?: 'Suivi individuel et préparation à la mobilité.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pillar-card text-center h-100">
                        <span class="pillar-num">03</span>
                        <div class="pillar-card-inner p-4">
                            <div class="pillar-icon"><i class="bi bi-globe-americas"></i></div>
                            <h4 class="h5 mb-2"><?= e($p3t ?: 'Ouverture internationale') ?></h4>
                            <p class="small text-muted mb-0"><?= e($p3 ?: 'Réseau de partenaires et opportunités d\'études à l\'étranger.') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function renderHomeSection_testimonials(array $testimonials): void
{
    if (!$testimonials) {
        return;
    }
    ?>
    <!-- 
    <section class="py-5 reveal" style="background: var(--hitas-ivory);">
        <div class="container">
            <div class="text-center mb-4">
                <div class="section-eyebrow">Ils font confiance à HITAS</div>
                <h2 class="section-title">Témoignages </h2>
            </div>
            <div class="row g-4">
                <?php foreach ($testimonials as $t): ?>
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="quote-mark">"</div>
                            <p class="mb-3"><?= e($t['content']) ?></p>
                            <div class="fw-semibold"><?= e($t['full_name']) ?></div>
                            <div class="small text-muted"><?= e($t['program']) ?> <?= $t['destination_country'] ? '· ' . e($t['destination_country']) : '' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>  
    -->
    <?php
}

function renderHomeSection_cta(): void
{
    $cta_sub = getSetting('home_cta_subtitle', 'Consultez les conditions d\'admission ou découvrez la filière qui vous correspond.');
    ?>
    <section class="py-5 reveal">
        <div class="container">
            <div class="cta-banner text-center">
                <a href="<?= BASE_URL ?>/programmes.php#programFinderForm" class="cta-programs-question display-font mb-2 d-inline-block">
                    Quelle filière voulez-vous étudier ? <i class="bi bi-arrow-right"></i>
                </a>
                <p class="mb-4" style="color: rgba(255,255,255,0.8);"><?= e($cta_sub) ?></p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= BASE_URL ?>/programmes.php" class="btn btn-hitas-primary btn-lg px-4">Voir les programmes</a>
                    <a href="<?= BASE_URL ?>/faq.php#admission-conditions" class="btn btn-hitas-outline btn-lg px-4">Conditions d'admission</a>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function renderFloatingContactButton(): void
{
    // Bouton de contact flottant, en bas à droite de la page Accueil :
    // "Appel direct" (numéro modifiable ci-dessous, ou depuis /admin/settings
    // via le réglage "call_phone") + WhatsApp.
    $directCallPhone = trim((string) getSetting('call_phone'));
    // Numéros WhatsApp du bouton de contact rapide : modifiables depuis
    // Réglages > Contact > "Numéros WhatsApp du bouton flottant".
    $quickWaSetting = trim((string) getSetting('floating_whatsapp_numbers'));
    $quickWhatsAppNumbers = $quickWaSetting !== ''
        ? array_values(array_filter(array_map('trim', explode('/', $quickWaSetting))))
        : ['658595959', '655555957'];
    ?>
    <div class="floating-contact dropdown dropup">
        <button class="floating-contact-btn dropdown-toggle" type="button" id="floatingContactBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Nous contacter">
            <i class="bi bi-telephone-fill"></i>
        </button>
        <ul class="dropdown-menu contact-dropdown-menu" aria-labelledby="floatingContactBtn">
            <li><h6 class="dropdown-header">Appel direct</h6></li>
            <li>
                <?php if ($directCallPhone !== ''): ?>
                    <a class="dropdown-item" href="tel:<?= e(str_replace(' ', '', $directCallPhone)) ?>">
                        <i class="bi bi-telephone-fill"></i>
                        <span><?= e($directCallPhone) ?></span>
                    </a>
                <?php else: ?>
                    <span class="dropdown-item disabled">
                        <i class="bi bi-telephone-fill"></i>
                        <span>Numéro bientôt disponible</span>
                    </span>
                <?php endif; ?>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">WhatsApp</h6></li>
            <?php foreach ($quickWhatsAppNumbers as $quickWaNumber): $quickWaUrl = getWhatsAppUrl($quickWaNumber); ?>
                <?php if ($quickWaUrl): ?>
                    <li>
                        <a class="dropdown-item" href="<?= e($quickWaUrl) ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-whatsapp"></i>
                            <span><?= e($quickWaNumber) ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

foreach ($homeSections as $sectionKey) {
    switch ($sectionKey) {
        case 'hero':
            renderHomeSection_hero($heroSlides, $latestNews, $partners, $codiplomationPartner ? (int)$codiplomationPartner['id'] : null);
            renderHomeTickerBanner($stats, $programCount, $partnerCount, $countryCount);
            break;
        case 'programs':
            renderHomeSection_programs($featuredPrograms, $programPartnerMap);
            break;
        case 'partners':
            renderHomeSection_partners($partners);
            break;
        case 'pillars':
            renderHomeSection_pillars();
            break;
        case 'testimonials':
            renderHomeSection_testimonials($testimonials);
            break;
        case 'cta':
            renderHomeSection_cta();
            break;
    }
}
renderFloatingContactButton();
?>

<?php include __DIR__ . '/includes/site_footer.php'; ?>
