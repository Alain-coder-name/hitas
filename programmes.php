<?php
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/filiere_classifier.php';

$pdo = getDB();
$programs = $pdo->query('SELECT * FROM programs WHERE is_published = 1 ORDER BY created_at DESC')->fetchAll();

// Chaque filière affichée correspond en pratique à une université bien précise
// (une ligne "programs" par partenaire qui l'enseigne). Le clic sur une filière
// doit donc emmener directement vers le site officiel de CETTE université,
// plutôt que vers notre fiche interne — d'où cette correspondance programme -> partenaire.
$programPartnerMap = [];
foreach ($pdo->query("SELECT pp.program_id, pp.partner_url, p.name AS partner_name, p.website AS partner_website
        FROM program_partners pp
        JOIN partners p ON p.id = pp.partner_id
        WHERE p.is_published = 1
        ORDER BY p.name") as $ppRow) {
    if (!isset($programPartnerMap[$ppRow['program_id']])) {
        $programPartnerMap[$ppRow['program_id']] = $ppRow;
    }
}
// Priorité : 1) la page précise de la filière chez le partenaire (partner_url,
// renseignée manuellement au cas par cas) 2) la page d'accueil du partenaire
// 3) à défaut, notre fiche interne.
function filiereLinkAttrs(array $programPartnerMap, int $programId, string $fallbackSlug): array
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

// "Filières phares" : sélection éditoriale (cochée depuis /admin/programs) —
// pas le catalogue complet, juste les plus demandées.
$featuredPrograms = $pdo->query('SELECT * FROM programs WHERE is_published = 1 AND is_featured = 1 ORDER BY featured_order ASC, created_at DESC')->fetchAll();
if (!$featuredPrograms) {
    $featuredPrograms = $programs;
}

// --- "Trouver ma filière" : recherche libre (autocomplétion) parmi les
// filières HITAS, croisée avec les universités partenaires par pays (optionnel).
// Une même filière (ex. "Génie mécanique") existe en plusieurs lignes distinctes,
// une par université partenaire qui l'enseigne. On déduplique donc la liste
// proposée à l'autocomplétion par titre normalisé (sans accent/casse), pour ne
// jamais afficher plusieurs fois la même filière sans indication d'université.
$finderPrograms = [];
$seenFiliereNames = [];
foreach ($programs as $p) {
    $norm = normalizeFiliereText($p['title']);
    if (!isset($seenFiliereNames[$norm])) {
        $seenFiliereNames[$norm] = true;
        $finderPrograms[] = $p;
    }
}
$finderCountries = $pdo->query("SELECT DISTINCT country FROM partners WHERE is_published = 1 AND country IS NOT NULL AND country <> '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

// --- Catalogue complet : recherche par nom + filtre par Groupe (l'un des
// 6 groupes officiels), pour que l'étudiant retrouve facilement une
// filière sans connaître le niveau exact. Le niveau (Bachelor/Master)
// reste affiché en badge sur chaque filière. Tri alphabétique A -> Z à
// l'intérieur de chaque groupe.
$catalogByGroup = [];
foreach ($programs as $p) {
    $group = trim((string)($p['category'] ?? '')) ?: 'Non classées';
    $catalogByGroup[$group][] = $p;
}
foreach ($catalogByGroup as &$catalogGroupItems) {
    usort($catalogGroupItems, fn($a, $b) => strcasecmp($a['title'], $b['title']));
}
unset($catalogGroupItems);
// Ordre d'affichage : les 6 groupes officiels dans l'ordre canonique,
// « Non classées » (le cas échéant) en dernier.
$catalogGroupOrder = array_values(array_intersect(filiereGroups(), array_keys($catalogByGroup)));
if (isset($catalogByGroup['Non classées'])) {
    $catalogGroupOrder[] = 'Non classées';
}

// Recherche libre par mot-clé : le client tape n'importe quel mot (ex.
// "informatique"), sans être obligé de choisir une suggestion exacte dans la
// liste déroulante. On fait ressortir TOUTES les filières dont le titre
// contient ce mot-clé — une recherche peut donc correspondre à plusieurs
// filières différentes, chacune proposée par une ou plusieurs universités —
// et c'est ensuite à l'étudiant de choisir parmi les résultats affichés.
$finderQuery = trim($_GET['q'] ?? '');
$finderSubmitted = $finderQuery !== '';
$finderCountry = trim($_GET['pays'] ?? '');
$finderGroups = [];

if ($finderSubmitted) {
    $queryNorm = normalizeFiliereText($finderQuery);

    // On regroupe les programmes correspondants par titre normalisé, pour
    // qu'une même filière enseignée par plusieurs universités n'apparaisse
    // qu'une fois, avec la liste complète de ses universités.
    $matchesByTitle = [];
    foreach ($programs as $p) {
        $norm = normalizeFiliereText($p['title']);
        // Recherche aussi sur les mots anglais équivalents (voir
        // filiereSearchKeywords) : un mot-clé tapé en anglais retrouve la
        // filière française correspondante.
        if ($queryNorm !== '' && str_contains(filiereSearchKeywords($p['title']), $queryNorm)) {
            $matchesByTitle[$norm]['title'] ??= $p['title'];
            $matchesByTitle[$norm]['slug'] ??= $p['slug'];
            $matchesByTitle[$norm]['ids'][] = (int)$p['id'];
        }
    }
    uasort($matchesByTitle, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    foreach ($matchesByTitle as $match) {
        $placeholders = implode(',', array_fill(0, count($match['ids']), '?'));

        $sql = "SELECT DISTINCT p.* FROM partners p
                JOIN program_partners pp ON pp.partner_id = p.id
                WHERE pp.program_id IN ($placeholders) AND p.is_published = 1";
        $params = $match['ids'];
        if ($finderCountry !== '') {
            $sql .= ' AND p.country = ?';
            $params[] = $finderCountry;
        }
        $sql .= ' ORDER BY p.name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $partnersInCountry = $stmt->fetchAll();

        // Si ce pays précis n'a pas cette filière, on montre quand même les
        // autres pays où elle existe plutôt que de la faire disparaître.
        $partnersAnyCountry = [];
        if (!$partnersInCountry && $finderCountry !== '') {
            $stmt = $pdo->prepare("SELECT DISTINCT p.* FROM partners p
                JOIN program_partners pp ON pp.partner_id = p.id
                WHERE pp.program_id IN ($placeholders) AND p.is_published = 1
                ORDER BY p.name");
            $stmt->execute($match['ids']);
            $partnersAnyCountry = $stmt->fetchAll();
        }

        if ($partnersInCountry || $partnersAnyCountry) {
            $finderGroups[] = [
                'title' => $match['title'],
                'slug' => $match['slug'],
                'partners' => $partnersInCountry,
                'partnersAnyCountry' => $partnersAnyCountry,
            ];
        }
    }
}

$pageTitle = 'Trouver ma filière — HITAS';
$pageDescription = 'Recherchez votre filière et découvrez instantanément les institutions partenaires de HITAS qui la proposent, dans le pays de votre choix.';
applySeoOverrides(getPageSeo('programmes'));
$currentPage = 'programmes';
$pageBackgroundKey = 'programmes';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero programmes-hero py-5">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Trouver ma filière</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow"><?= e(getSetting('programmes_hero_eyebrow', 'Trouvez votre voie')) ?></span>
                <h1 class="display-font mt-3"><?= e(getSetting('programmes_hero_title', 'Trouver ma filière')) ?></h1>
                <p class="lead"><?= e(getSetting('programmes_hero_lead', "Vous savez ce que vous voulez étudier, mais pas encore où le faire ? Indiquez votre filière et découvrez en un instant quelles institutions partenaires de HITAS vous y préparent, et dans quel pays.")) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal program-finder-section">
    <div class="container">
        <div class="program-finder-card">
            <div class="text-center mb-4">
                <span class="section-eyebrow">Pas encore fixé sur votre filière ?</span>
                <h2 class="section-title">Quelle filière voulez-vous étudier ?</h2>
                <p class="text-muted mx-auto" style="max-width:640px;">Tapez le nom de la filière qui vous intéresse, et si vous le souhaitez un pays de destination : nous recherchons pour vous parmi toutes nos institutions partenaires.</p>
            </div>
            <form action="<?= BASE_URL ?>/programmes.php" method="get" class="program-finder-form" id="programFinderForm" novalidate>
                <div class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-5">
                        <label class="form-label" for="finderFiliereText">Filière</label>
                        <div class="finder-autocomplete"
                             data-programs='<?= e(json_encode(array_values(array_map(static fn($p) => ['id' => (int)$p['id'], 'title' => $p['title'], 'keywords' => filiereSearchKeywords($p['title'])], $finderPrograms)))) ?>'>
                            <input type="text"
                                   name="q"
                                   id="finderFiliereText"
                                   class="form-control finder-autocomplete-input"
                                   placeholder="Ex : Informatique, Génie civil..."
                                   autocomplete="off"
                                   role="combobox"
                                   aria-expanded="false"
                                   aria-autocomplete="list"
                                   aria-controls="finderFiliereList"
                                   value="<?= e($finderQuery) ?>">
                            <ul class="finder-autocomplete-list" id="finderFiliereList" role="listbox" hidden></ul>
                        </div>
                        <div class="form-text">Tapez un mot-clé (ex. « informatique ») : toutes les filières correspondantes s'affichent, avec leurs institutions — pas besoin de choisir dans la liste.</div>
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
                    <?php if (!$finderGroups): ?>
                        <div class="text-center">
                            <p class="text-muted mb-3">Aucune filière ne correspond à « <?= e($finderQuery) ?> » chez nos partenaires actuellement. Notre équipe peut vous orienter vers d'autres options.</p>
                            <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-outline">Nous contacter</a>
                        </div>
                    <?php else: ?>
                        <h3 class="program-finder-results-title">
                            <?= count($finderGroups) ?> filière<?= count($finderGroups) > 1 ? 's' : '' ?> correspond<?= count($finderGroups) > 1 ? 'ent' : '' ?> à « <?= e($finderQuery) ?> »<?= $finderCountry !== '' ? ' — pays souhaité : ' . e($finderCountry) : '' ?>
                        </h3>
                        <?php foreach ($finderGroups as $group): ?>
                            <div class="finder-result-group mb-4 pb-4 border-bottom">
                                <h4 class="h5 mb-2">
                                    <a href="<?= BASE_URL ?>/programme.php?slug=<?= urlencode($group['slug']) ?>"><?= e($group['title']) ?></a>
                                </h4>
                                <?php if ($group['partners']): ?>
                                    <p class="small text-muted mb-2"><?= count($group['partners']) ?> institution<?= count($group['partners']) > 1 ? 's' : '' ?> partenaire<?= count($group['partners']) > 1 ? 's' : '' ?><?= $finderCountry !== '' ? ' en ' . e($finderCountry) : '' ?></p>
                                    <div class="row g-3">
                                        <?php foreach ($group['partners'] as $partner): ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="partner-card p-3 h-100">
                                                    <?php if ($partner['logo']): ?>
                                                        <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="mb-3 partner-logo" loading="lazy">
                                                    <?php endif; ?>
                                                    <h5 class="h6 mb-1"><?= e($partner['name']) ?></h5>
                                                    <div class="text-muted mb-2"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></div>
                                                    <a href="<?= BASE_URL ?>/partenaire.php?id=<?= (int)$partner['id'] ?>" class="small fw-semibold d-block mb-1">Voir la fiche partenaire <i class="bi bi-arrow-right"></i></a>
                                                    <?php if ($partner['website']): ?>
                                                        <a href="<?= e($partner['website']) ?>" target="_blank" rel="noopener" class="small text-muted">Site officiel <i class="bi bi-box-arrow-up-right"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="small text-muted mb-2">Pas proposée en <?= e($finderCountry) ?> pour le moment — mais elle l'est ailleurs :</p>
                                    <div class="row g-3">
                                        <?php foreach ($group['partnersAnyCountry'] as $partner): ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="partner-card p-3 h-100">
                                                    <?php if ($partner['logo']): ?>
                                                        <img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" alt="<?= e($partner['name']) ?>" class="mb-3 partner-logo" loading="lazy">
                                                    <?php endif; ?>
                                                    <h5 class="h6 mb-1"><?= e($partner['name']) ?></h5>
                                                    <div class="text-muted mb-2"><?= e($partner['country']) ?><?php if (!empty($partner['flag_emoji'])): ?> <span class="flag-emoji"><?= e($partner['flag_emoji']) ?></span><?php endif; ?></div>
                                                    <a href="<?= BASE_URL ?>/partenaire.php?id=<?= (int)$partner['id'] ?>" class="small fw-semibold">Voir la fiche partenaire <i class="bi bi-arrow-right"></i></a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5 mt-4 reveal" id="filieres-phares">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <div class="section-eyebrow">Vous hésitez encore ?</div>
                <h2 class="section-title mb-0">Filières phares</h2>
                <p class="text-muted mt-2 mb-0" style="max-width:620px;">Si vous n'avez pas encore d'idée précise, voici les filières que nos étudiants choisissent le plus souvent — un bon point de départ pour explorer les possibilités.</p>
            </div>
        </div>

        <?php if (!$featuredPrograms): ?>
            <p class="text-muted">Nos filières seront bientôt disponibles ici.</p>
        <?php else: ?>
            <?php $featuredGroups = array_chunk($featuredPrograms, 3); ?>
            <div class="pillars-slideshow" data-duration="6000">
                <?php foreach ($featuredGroups as $gi => $group): ?>
                    <div class="pillars-slide<?= $gi === 0 ? ' is-active' : '' ?>">
                        <div class="row g-4">
                            <?php foreach ($group as $p): ?>
                                <div class="col-md-4 program-card-cell">
                                    <div class="program-card">
                                        <div class="program-img" style="<?= $p['image'] ? "background-image:url('" . UPLOAD_URL . "programs/" . e($p['image']) . "')" : '' ?>"></div>
                                        <div class="p-3">
                                            <?php if (!empty($p['degree_level'])): ?><span class="badge bg-hitas-gold-badge mb-2 me-1 d-inline-block"><?= e($p['degree_level']) ?></span><?php endif; ?>
                                            <h5 class="mb-2"><?= e($p['title']) ?></h5>
                                            <p class="small text-muted mb-3"><?= e($p['short_description']) ?></p>
                                            <?php $flink = filiereLinkAttrs($programPartnerMap, (int)$p['id'], $p['slug']); ?>
                                            <a href="<?= e($flink['href']) ?>" class="small fw-semibold"<?= $flink['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= $flink['external'] ? 'Voir chez le partenaire' : 'En savoir plus' ?> <i class="bi <?= $flink['external'] ? 'bi-box-arrow-up-right' : 'bi-arrow-right' ?>"></i></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($featuredGroups) > 1): ?>
                <div class="pillars-dots">
                    <?php foreach ($featuredGroups as $gi => $group): ?>
                        <span class="pillars-dot<?= $gi === 0 ? ' is-active' : '' ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5 mt-4 reveal" id="toutes-les-filieres">
    <div class="container">
        <div class="mb-4">
            <div class="section-eyebrow">Le catalogue complet</div>
            <h2 class="section-title mb-0">Toutes nos filières</h2>
            <p class="text-muted mt-2 mb-0" style="max-width:620px;">Recherchez une filière par nom, ou filtrez par domaine — toutes triées de A à Z.</p>
        </div>

        <?php if (!$catalogByGroup): ?>
            <p class="text-muted">Nos filières seront bientôt disponibles ici.</p>
        <?php else: ?>
            <div class="filiere-search-toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-4">
                        <label class="visually-hidden" for="filiereSearchInput">Rechercher une filière</label>
                        <input type="search" id="filiereSearchInput" class="form-control" placeholder="Rechercher une filière (ex : informatique, droit...)">
                    </div>
                    <div class="col-lg-8">
                        <div class="filiere-group-filters" id="filiereGroupFilters">
                            <a href="#toutes-les-filieres" class="filiere-group-chip active" data-group="">Toutes les filières</a>
                            <?php foreach ($catalogGroupOrder as $groupName): ?>
                                <a href="#cycle-<?= e(str_replace(' ', '-', normalizeFiliereText($groupName))) ?>" class="filiere-group-chip" data-group="<?= e($groupName) ?>"><?= e($groupName) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="filiereCatalogResults">
                <?php foreach ($catalogGroupOrder as $groupName): ?>
                    <div class="filiere-group-section mb-5" id="cycle-<?= e(str_replace(' ', '-', normalizeFiliereText($groupName))) ?>" data-group="<?= e($groupName) ?>">
                        <h3 class="h4 mb-3"><?= e($groupName) ?> <span class="badge bg-hitas-gold-badge"><?= count($catalogByGroup[$groupName]) ?></span></h3>
                        <div class="row g-3">
                            <?php foreach ($catalogByGroup[$groupName] as $p): ?>
                                <div class="col-md-6 col-lg-4 filiere-item" data-name="<?= e(filiereSearchKeywords($p['title'])) ?>">
                                    <div class="filiere-item-card p-3 h-100">
                                        <?php if (!empty($p['degree_level'])): ?><span class="badge bg-light text-dark border mb-2"><?= e($p['degree_level']) ?></span><?php endif; ?>
                                        <?php $flink = filiereLinkAttrs($programPartnerMap, (int)$p['id'], $p['slug']); ?>
                                        <a href="<?= e($flink['href']) ?>" class="fw-semibold d-block"<?= $flink['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($p['title']) ?><?php if ($flink['external']): ?> <i class="bi bi-box-arrow-up-right small"></i><?php endif; ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p class="text-muted text-center py-4" id="filiereNoResults" hidden>Aucune filière ne correspond à votre recherche.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Recherche + filtre par groupe du catalogue « Toutes nos filières » :
// entièrement côté client (la liste est déjà dans la page), insensible à
// la casse et aux accents ("ingenierie" retrouve "Ingénierie").
(function () {
    var searchInput = document.getElementById('filiereSearchInput');
    var chips = document.querySelectorAll('.filiere-group-chip');
    var sections = document.querySelectorAll('.filiere-group-section');
    var noResults = document.getElementById('filiereNoResults');
    if (!searchInput || !sections.length) return;

    var activeGroup = '';

    function normalizeSearch(text) {
        return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    }

    function applyFilters() {
        var term = normalizeSearch(searchInput.value);
        var anyVisible = false;

        sections.forEach(function (section) {
            var groupMatches = !activeGroup || section.dataset.group === activeGroup;
            var visibleCount = 0;

            section.querySelectorAll('.filiere-item').forEach(function (item) {
                var show = groupMatches && (!term || item.dataset.name.indexOf(term) !== -1);
                item.hidden = !show;
                if (show) visibleCount++;
            });

            section.hidden = visibleCount === 0;
            if (visibleCount > 0) anyVisible = true;
        });

        noResults.hidden = anyVisible;
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            activeGroup = chip.dataset.group;
            applyFilters();
        });
    });

    searchInput.addEventListener('input', applyFilters);

    // Lien direct vers un cycle (ex: venant de la page d'un partenaire) :
    // #cycle-xxx doit pré-sélectionner et filtrer le bon groupe au chargement.
    if (location.hash && location.hash.indexOf('#cycle-') === 0) {
        var targetSection = document.getElementById(location.hash.slice(1));
        if (targetSection) {
            var matchingChip = document.querySelector('.filiere-group-chip[data-group="' + (targetSection.dataset.group || '').replace(/"/g, '\\"') + '"]');
            if (matchingChip) {
                chips.forEach(function (c) { c.classList.remove('active'); });
                matchingChip.classList.add('active');
                activeGroup = matchingChip.dataset.group;
                applyFilters();
                setTimeout(function () { targetSection.scrollIntoView({ block: 'start' }); }, 50);
            }
        }
    }
})();
</script>

<?php include __DIR__ . '/includes/site_footer.php';
