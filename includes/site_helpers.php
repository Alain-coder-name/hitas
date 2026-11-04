<?php
// =========================================================
// Fonctions utilitaires - Site public HITAS
// =========================================================

require_once __DIR__ . '/../config/database.php';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Transforme un texte brut (saisi en admin, avec sauts de ligne) en
 * paragraphes HTML sûrs : chaque ligne vide sépare un nouveau <p>, les
 * sauts de ligne simples à l'intérieur d'un paragraphe deviennent <br>.
 * Le texte est échappé (via e()) avant tout formatage — jamais de HTML brut.
 */
function textToParagraphs(?string $text): string
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }
    $blocks = preg_split('/\r?\n\s*\r?\n/', $text);
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . "</p>\n";
    }
    return $html;
}

/**
 * Récupère tous les paramètres généraux (clé => valeur), avec mise en cache statique.
 */
function getSettings(): array
{
    static $settings = null;

    if ($settings === null) {
        $pdo = getDB();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = array_column($rows, 'setting_value', 'setting_key');
    }

    return $settings;
}

function getSetting(string $key, string $default = ''): string
{
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

/**
 * Préfixe BASE_URL sur un lien interne saisi en base (ex: '/programmes.php'),
 * sans toucher aux liens externes (http://, https://, mailto:...) saisis via l'admin.
 */
function siteUrl(string $path): string
{
    if ($path === '' || preg_match('~^([a-z][a-z0-9+.\-]*:|//)~i', $path)) {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

function normalizePhoneForWhatsApp(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    return $digits;
}

function getWhatsAppUrl(string $phone): ?string
{
    $normalized = normalizePhoneForWhatsApp($phone);
    return $normalized === '' ? null : 'https://wa.me/' . $normalized;
}

function getGoogleMapsUrl(string $address): string
{
    $query = rawurlencode(getMapAddress($address));
    return 'https://www.google.com/maps/search/?api=1&query=' . $query;
}

function getGoogleMapsEmbedUrl(string $address): string
{
    $query = rawurlencode(getMapAddress($address));
    return 'https://www.google.com/maps?q=' . $query . '&output=embed';
}

function parseAddressLines(string $address): array
{
    $parts = preg_split('/\s*[\/\\\\]\s*/', $address);
    return array_values(array_filter(array_map('trim', $parts), static fn($line) => $line !== ''));
}

function getMapAddress(string $address): string
{
    $parts = parseAddressLines($address);
    return $parts[0] ?? trim($address);
}

function getFacebookUrl(string $facebook): ?string
{
    $facebook = trim($facebook);
    if ($facebook === '') {
        return null;
    }
    if (preg_match('~^https?://~i', $facebook)) {
        return $facebook;
    }
    $facebook = preg_replace('~^www\.~i', '', $facebook);
    if (stripos($facebook, 'facebook.com') !== false) {
        return 'https://' . $facebook;
    }
    $slug = preg_replace('/[^A-Za-z0-9]+/', '', $facebook);
    return 'https://www.facebook.com/' . $slug;
}

/**
 * Média d'un élément de la table "gallery" (photo et/ou vidéo) : type,
 * URL du média en grand, URL d'aperçu (photo) — pour la visionneuse
 * (assets/js/lightbox.js). Une vidéo seule (sans photo) est valide.
 */
function galleryMedia(array $item): array
{
    $isVideo = !empty($item['video']);
    $poster = !empty($item['image']) ? UPLOAD_URL . 'gallery/' . $item['image'] : '';
    return [
        'type'   => $isVideo ? 'video' : 'image',
        'src'    => UPLOAD_URL . 'gallery/' . ($isVideo ? $item['video'] : $item['image']),
        'poster' => $poster,
    ];
}

/** Attributs HTML data-lb-* + accessibilité d'une carte de galerie cliquable. */
function galleryLightboxAttrs(array $item, string $group, string $title, string $text = ''): string
{
    $m = galleryMedia($item);
    $label = ($m['type'] === 'video' ? 'Lire la vidéo : ' : 'Agrandir la photo : ') . $title;
    return 'tabindex="0" role="button"'
        . ' aria-label="' . e($label) . '"'
        . ' data-lb-group="' . e($group) . '"'
        . ' data-lb-type="' . $m['type'] . '"'
        . ' data-lb-src="' . e($m['src']) . '"'
        . ' data-lb-poster="' . e($m['type'] === 'video' ? $m['poster'] : '') . '"'
        . ' data-lb-thumb="' . e($m['poster']) . '"'
        . ' data-lb-title="' . e($title) . '"'
        . ' data-lb-text="' . e($text) . '"';
}

/** Contenu visuel (aperçu photo, ou vidéo + badge lecture) d'une carte de galerie. */
function galleryCardMediaHtml(array $item): string
{
    $m = galleryMedia($item);
    $html = '';
    if ($m['type'] === 'video') {
        if (!$m['poster']) {
            $html .= '<video class="gallery-card-video" src="' . e($m['src']) . '#t=0.5" preload="metadata" muted playsinline aria-hidden="true"></video>';
        }
        $html .= '<span class="media-play-badge" aria-hidden="true"><i class="bi bi-play-fill"></i></span>';
    }
    return $html;
}

/**
 * Retourne l'URL vers laquelle un article/actualité doit pointer.
 * Si c'est une publicité avec un lien externe renseigné, on redirige vers ce lien.
 * Sinon, on renvoie vers la page article interne classique.
 */
function newsTargetUrl(array $article): string
{
    if (!empty($article['is_advertisement']) && !empty($article['external_link'])) {
        return $article['external_link'];
    }
    return BASE_URL . '/article.php?slug=' . urlencode($article['slug']);
}

/** Attributs HTML à ajouter au lien d'une actualité (nouvel onglet + sécurité pour les liens externes) */
function newsTargetAttrs(array $article): string
{
    if (!empty($article['is_advertisement']) && !empty($article['external_link'])) {
        return 'target="_blank" rel="noopener sponsored"';
    }
    return '';
}

function parseContactEmails(string $emails): array
{
    $parts = preg_split('/[;,\/]+/', $emails);
    return array_values(array_filter(array_map('trim', $parts), static fn($email) => $email !== ''));
}

/* =========================================================
 * Multi-campus (public)
 * ========================================================= */

/** Retourne tous les campus actifs, triés pour affichage. */
function getCampuses(): array
{
    static $campuses = null;
    if ($campuses === null) {
        try {
            $pdo = getDB();
            $campuses = $pdo->query('SELECT * FROM campuses WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
        } catch (Throwable $e) {
            $campuses = [];
        }
    }
    return $campuses;
}

function getCampusByCode(string $code): ?array
{
    foreach (getCampuses() as $c) {
        if ($c['code'] === $code) {
            return $c;
        }
    }
    return null;
}

function getCampusById(?int $id): ?array
{
    if (!$id) return null;
    foreach (getCampuses() as $c) {
        if ((int)$c['id'] === $id) {
            return $c;
        }
    }
    return null;
}

/** Code du campus choisi par le visiteur (cookie), 'douala' par défaut. */
function getCurrentCampusCode(): string
{
    $code = $_COOKIE['hitas_campus'] ?? '';
    if ($code && getCampusByCode($code)) {
        return $code;
    }
    return 'douala';
}

/** Campus actuellement sélectionné côté site public, avec repli sur les paramètres généraux. */
/**
 * Éléments du menu de navigation public, gérés depuis l'admin (Lot A).
 */
function getNavMenu(): array
{
    static $items = null;
    if ($items === null) {
        $pdo = getDB();
        $items = $pdo->query('SELECT * FROM nav_menu WHERE is_visible = 1 ORDER BY order_index, id')->fetchAll();
    }
    return $items;
}

/**
 * Ligne SEO (meta_title / meta_description / og_image) d'une page statique,
 * ou null si absente. Le contenu (content) n'est pas toujours utilisé :
 * certaines pages n'existent dans "pages" que pour porter leur SEO.
 */
function getPageSeo(string $slug): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Applique les surcharges SEO d'une ligne "pages"/"programs"/"news" aux
 * variables $pageTitle / $pageDescription / $pageOgImage si elles sont
 * renseignées. À appeler AVANT d'inclure site_header.php.
 * Accepte aussi `false` (valeur renvoyée par PDOStatement::fetch() quand
 * aucune ligne ne correspond) en plus de `null`/`array`, pour rester sans
 * risque même quand l'appelant passe directement un fetch() sans repli.
 */
function applySeoOverrides($row, string $imageSubfolder = 'pages'): void
{
    global $pageTitle, $pageDescription, $pageOgImage;
    if (!$row || !is_array($row)) {
        return;
    }
    if (!empty($row['meta_title'])) {
        $pageTitle = $row['meta_title'];
    }
    if (!empty($row['meta_description'])) {
        $pageDescription = $row['meta_description'];
    }
    if (!empty($row['og_image'])) {
        $pageOgImage = UPLOAD_URL . $imageSubfolder . '/' . $row['og_image'];
    }
}

/* =========================================================
 * Arrière-plans de page (Lot C : décoratifs, 100% optionnels)
 * ---------------------------------------------------------
 * Chaque page publique (hors Accueil) peut afficher une image
 * d'arrière-plan décorative, gérable depuis /admin/page_backgrounds.
 * - Si l'admin n'a jamais touché à la page : image par défaut
 *   (une photo déjà présente dans /uploads, cohérente avec le thème).
 * - Si l'admin a téléversé une image : cette image est utilisée.
 * - Si l'admin a explicitement supprimé l'image : aucune image,
 *   le CSS applique automatiquement un arrière-plan de secours.
 * Dans tous les cas, l'absence d'image ne casse jamais la mise en page.
 * ========================================================= */

/**
 * Définit, pour chaque page gérable, un libellé admin et une image par
 * défaut (chemin relatif à UPLOAD_URL). Arrière-plan unique et commun à
 * tout le site : le logo HITAS en filigrane clair
 * (assets/img/page-bg-hitas-watermark.png généré à partir du logo
 * officiel, éclairci pour rester en harmonie avec le texte au premier
 * plan), copié dans uploads/gallery/ pour rester résolu via UPLOAD_URL —
 * y compris l'accueil (clé "accueil").
 */
function pageBackgroundDefs(): array
{
    $watermark = 'gallery/hitas-watermark-bg.png';
    return [
        'accueil'    => ['label' => 'Accueil',    'default' => $watermark],
        'a-propos'   => ['label' => 'À propos',   'default' => $watermark],
        'programmes' => ['label' => 'Trouver ma filière', 'default' => $watermark],
        'partenaires'=> ['label' => 'Partenaires','default' => $watermark],
        'equipe'     => ['label' => 'Équipe',     'default' => $watermark],
        'actualites' => ['label' => 'Actualités', 'default' => $watermark],
        'postulez'   => ['label' => 'Postulez',   'default' => $watermark],
        'faq'        => ['label' => 'FAQ',        'default' => $watermark],
        'admissions' => ['label' => 'Admissions', 'default' => $watermark],
    ];
}

/**
 * Résout l'état actuel de l'arrière-plan d'une page :
 * - 'custom'  => image téléversée depuis l'admin (uploads/pagebg/…)
 * - 'default' => image par défaut fournie avec le site
 * - 'none'    => aucune image (supprimée par l'admin, ou page inconnue) :
 *                le CSS applique alors un arrière-plan de secours.
 */
function getPageBackgroundInfo(string $key): array
{
    $defs = pageBackgroundDefs();
    $def = $defs[$key] ?? null;
    $custom = getSetting('pagebg_' . $key, '');

    if ($custom === '__none__') {
        return ['source' => 'none', 'url' => null];
    }
    if ($custom !== '') {
        return ['source' => 'custom', 'url' => UPLOAD_URL . 'pagebg/' . $custom, 'filename' => $custom];
    }
    if ($def) {
        return ['source' => 'default', 'url' => UPLOAD_URL . $def['default']];
    }
    return ['source' => 'none', 'url' => null];
}

/** Raccourci : renvoie uniquement l'URL de l'image d'arrière-plan (ou null si aucune). */
function getPageBackgroundUrl(?string $key): ?string
{
    if (!$key) {
        return null;
    }
    return getPageBackgroundInfo($key)['url'];
}

/**
 * Coordonnées de contact de HITAS (adresse, téléphone, email, recherche
 * Maps) — UNE SEULE source : les Réglages (admin > Paramètres > Contact).
 * (Avant, ces informations pouvaient aussi venir d'une table "campuses"
 * séparée, ce qui créait deux endroits à modifier pour le même résultat —
 * simplifié depuis que HITAS n'opère qu'un seul campus.)
 */
function getCurrentCampus(): array
{
    return [
        'name' => getSetting('site_name', 'HITAS'),
        'address' => getSetting('address'),
        'phone' => getSetting('contact_phone'),
        'email' => getSetting('contact_email'),
        'maps_query' => getSetting('maps_query') ?: getSetting('address'),
    ];
}
