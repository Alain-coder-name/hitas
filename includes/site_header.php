<?php
require_once __DIR__ . '/site_helpers.php';
$siteName = getSetting('site_name', 'HITAS');
// Logo officiel HITAS : fixe, non modifiable depuis l'admin.
$logo = BASE_URL . '/assets/img/logo-hitas-officiel.jpg';
$pageTitle = $pageTitle ?? $siteName;
$pageDescription = $pageDescription ?? "HITAS - Hanseatic Institute of Technology and Applied Sciences. Une à deux années pour préparer votre départ vers une institution partenaire à l'étranger.";
$currentPage = $currentPage ?? '';
$favicon = getSetting('favicon', '');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function siteNavLink(string $key, string $href, string $label, string $currentPage): string
{
    $active = $currentPage === $key ? 'active' : '';
    $url = (stripos($href, 'http') === 0) ? $href : (BASE_URL . $href);
    $target = (stripos($href, 'http') === 0) ? ' target="_blank" rel="noopener noreferrer"' : '';
    return '<li class="nav-item"><a class="nav-link ' . $active . '" href="' . e($url) . '"' . $target . '>' . e($label) . '</a></li>';
}

$canonicalUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'hitas.org') . ($_SERVER['REQUEST_URI'] ?? '/');
// $pageOgImage peut être défini par la page appelante (SEO par page/programme/actualité)
$ogImage = $pageOgImage ?? $logo;

// Arrière-plan décoratif de page (le filigrane du logo HITAS, commun à
// tout le site) : la page appelante définit $pageBackgroundKey AVANT
// d'inclure ce fichier (ex: $pageBackgroundKey = 'a-propos';) — l'accueil
// définit désormais aussi la sienne ('accueil'), voir index.php.
$pageBackgroundUrl = getPageBackgroundUrl($pageBackgroundKey ?? null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <?php if ($favicon): ?><link rel="icon" href="<?= UPLOAD_URL ?>settings/<?= e($favicon) ?>"><?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/site.css?v=<?= @filemtime(__DIR__ . '/../assets/css/site.css') ?: '1' ?>" rel="stylesheet">
    <?php
    // Inject CSS variables from settings so admin color changes apply immediately
    $primary = getSetting('primary_color', '#2E86DE');
    $gold = getSetting('accent_color', '#9c7c3c');
    $ivory = getSetting('ivory_color', '#f4f1ea');
    ?>
    <style>
        :root {
            --hitas-navy: <?= e($primary) ?>;
            --hitas-gold: <?= e($gold) ?>;
            --hitas-ivory: <?= e($ivory) ?>;
        }
    </style>
</head>
<body class="<?= $pageBackgroundUrl ? 'has-page-bg' : '' ?>"<?= $pageBackgroundUrl ? ' style="--page-bg-image: url(\'' . e($pageBackgroundUrl) . '\')"' : '' ?>>

<a href="#main-content" class="skip-link">Aller au contenu principal</a>

<nav class="navbar navbar-expand-lg site-navbar sticky-top py-2" id="siteNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <img src="<?= e($logo) ?>" class="site-logo-img" alt="<?= e($siteName) ?>">
            <span class="site-brand-text site-brand-text--solo">
                <strong>HITAS</strong>
                <span class="site-brand-subtitle" translate="no">Hanseatic Institute of Technology and Applied Sciences</span>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="mainNav">
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <?php foreach (getNavMenu() as $navItem): ?>
                    <?= siteNavLink($navItem['page_key'] ?? '', $navItem['url'], $navItem['label'], $currentPage) ?>
                <?php endforeach; ?>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-hitas-primary btn-postulez px-4" href="<?= BASE_URL ?>/postulez.php">Postulez</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="lang-switcher-bar notranslate" translate="no">
    <div class="container d-flex align-items-center justify-content-end flex-wrap gap-1">
        <i class="bi bi-translate lang-switcher-icon" aria-hidden="true"></i>
        <div class="lang-switcher-inline" id="langSwitcher">
            <button type="button" class="lang-switcher-item" data-lang="fr">🇫🇷 Français</button>
            <button type="button" class="lang-switcher-item" data-lang="en">🇬🇧 English</button>
            <button type="button" class="lang-switcher-item" data-lang="es">🇪🇸 Español</button>
            <button type="button" class="lang-switcher-item" data-lang="de">🇩🇪 Deutsch</button>
            <button type="button" class="lang-switcher-item" data-lang="it">🇮🇹 Italiano</button>
            <button type="button" class="lang-switcher-item" data-lang="hi">🇮🇳 हिन्दी</button>
        </div>
    </div>
</div>
<div id="google_translate_element"></div>
<script>
// Sélecteur de langue : s'appuie sur le moteur Google Translate (gratuit,
// couvre tout le contenu y compris les filières en base) mais avec une
// interface entièrement personnalisée — le widget Google d'origine reste
// invisible (voir CSS .goog-te-banner-frame / #google_translate_element).
// Le widget Google est piloté DIRECTEMENT (comme le ferait un clic humain
// sur son propre menu) plutôt que par le seul cookie googtrans, qui ne se
// déclenche pas toujours de façon fiable tout seul au chargement.
(function () {
    var SUPPORTED_LANGS = ['en', 'es', 'de', 'it', 'hi'];
    var STORAGE_KEY = 'hitas_lang_choice';

    function setLangCookie(lang) {
        var expired = 'Thu, 01 Jan 1970 00:00:00 UTC';
        if (lang === 'fr') {
            document.cookie = 'googtrans=; expires=' + expired + '; path=/;';
            document.cookie = 'googtrans=; expires=' + expired + '; path=/; domain=' + location.hostname + ';';
        } else {
            document.cookie = 'googtrans=/fr/' + lang + '; path=/;';
            document.cookie = 'googtrans=/fr/' + lang + '; path=/; domain=' + location.hostname + ';';
        }
    }

    // Essaie de piloter directement le <select class="goog-te-combo"> que
    // Google injecte dans #google_translate_element. Réessaie pendant
    // quelques secondes tant que le widget n'est pas encore prêt (son
    // script se charge de façon asynchrone).
    function driveWidget(lang, attemptsLeft) {
        var combo = document.querySelector('#google_translate_element select.goog-te-combo');
        if (combo) {
            combo.value = lang === 'fr' ? '' : lang;
            combo.dispatchEvent(new Event('change'));
            return true;
        }
        if (attemptsLeft > 0) {
            setTimeout(function () { driveWidget(lang, attemptsLeft - 1); }, 300);
        }
        return false;
    }

    function setActiveButton(lang) {
        var wrapper = document.getElementById('langSwitcher');
        if (!wrapper) return;
        wrapper.querySelectorAll('.lang-switcher-item').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-lang') === lang);
        });
    }

    window.hitasSetLanguage = function (lang, persist) {
        if (persist) {
            try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) { /* ignore */ }
        }
        setLangCookie(lang);
        setActiveButton(lang);
        if (lang === 'fr') {
            // Revenir au français : le plus fiable reste de recharger la
            // page telle que le serveur l'a générée (sans overlay Google).
            location.reload();
            return;
        }
        driveWidget(lang, 20);
    };

    var stored = null;
    try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) { /* ignore */ }

    // Interface du sélecteur : toutes les langues sont déjà visibles sur la
    // bande (pas de menu à ouvrir) — un simple clic suffit. On marque la
    // langue active et on relance la traduction si un choix a déjà été fait
    // lors d'une visite précédente.
    document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.getElementById('langSwitcher');
        if (!wrapper) return;

        var current = stored || 'fr';
        setActiveButton(current);

        wrapper.querySelectorAll('button[data-lang]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.hitasSetLanguage(btn.getAttribute('data-lang'), true);
            });
        });

        // Une langue avait déjà été choisie lors d'une visite précédente :
        // on relance la traduction sur cette nouvelle page automatiquement.
        if (current !== 'fr' && SUPPORTED_LANGS.indexOf(current) !== -1) {
            driveWidget(current, 20);
        } else if (!stored) {
            // Première visite, aucun choix explicite : on propose la langue
            // du navigateur si elle fait partie des 5 langues disponibles.
            var browserLang = (navigator.language || navigator.userLanguage || '').slice(0, 2).toLowerCase();
            if (SUPPORTED_LANGS.indexOf(browserLang) !== -1) {
                try { localStorage.setItem(STORAGE_KEY, browserLang); } catch (e) { /* ignore */ }
                setLangCookie(browserLang);
                setActiveButton(browserLang);
                driveWidget(browserLang, 20);
            }
        }
    });
})();

function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'fr',
        includedLanguages: 'en,es,de,it,hi',
        autoDisplay: false
    }, 'google_translate_element');
}
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>

<main id="main-content">
