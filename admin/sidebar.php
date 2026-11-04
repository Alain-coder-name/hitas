<?php
$current = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$adminRole = $_SESSION['admin_role'] ?? '';

function navLink(string $dir, string $href, string $icon, string $label, string $currentDir): string
{
    $active = $currentDir === $dir ? 'active' : '';
    return '<a href="' . BASE_URL . $href . '" class="nav-link ' . $active . '"><i class="bi ' . $icon . '"></i> <span>' . $label . '</span></a>';
}

// Sous-entrées regroupées sous l'entrée dépliable "Contenu"
$contentDirs = ['programs', 'partners', 'news', 'press', 'team', 'testimonials', 'faq', 'pages', 'campus_highlights', 'page_galleries'];
$contentOpen = in_array($currentDir, $contentDirs, true);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">HITAS <span>Admin</span></div>
    <nav class="nav flex-column">
        <a href="<?= BASE_URL ?>/admin/index.php" class="nav-link <?= $current === 'index.php' && $currentDir === 'admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Tableau de bord</span>
        </a>

        <?php if ($adminRole === 'redacteur'): ?>
            <!-- Rédacteur : uniquement les Filières, rien d'autre. -->
            <div class="nav-section">Contenu</div>
            <?= navLink('programs', '/admin/programs/list.php', 'bi-mortarboard', 'Filières', $currentDir) ?>

        <?php elseif ($adminRole === 'boss'): ?>
            <!-- Direction : tableau de bord (synthèses), Actualités, Messages. -->
            <div class="nav-section">Suivi</div>
            <?= navLink('news', '/admin/news/list.php', 'bi-newspaper', 'Actualités', $currentDir) ?>
            <?= navLink('messages', '/admin/messages/list.php', 'bi-envelope', 'Messages reçus', $currentDir) ?>

        <?php else: ?>
            <!-- Super-administrateur : accès complet. -->
            <div class="nav-section">Accueil</div>
            <?= navLink('home', '/admin/home/index.php', 'bi-house', 'Contenu de l\'accueil', $currentDir) ?>
            <?= navLink('home_sections', '/admin/home_sections/list.php', 'bi-layout-three-columns', 'Sections & blocs', $currentDir) ?>
            <?= navLink('hero_slides', '/admin/hero_slides/list.php', 'bi-images', 'Diaporama accueil', $currentDir) ?>
            <?= navLink('stats', '/admin/stats/list.php', 'bi-bar-chart-line', 'Chiffres clés (bande défilante)', $currentDir) ?>
            <?= navLink('banners', '/admin/banners/index.php', 'bi-layout-text-window', 'Bandeaux des pages', $currentDir) ?>
            <?= navLink('page_backgrounds', '/admin/page_backgrounds/index.php', 'bi-image', 'Arrière-plans des pages', $currentDir) ?>

            <div class="nav-section">Contenu</div>
            <a href="#contentSubmenu" class="nav-link d-flex align-items-center justify-content-between <?= $contentOpen ? 'active' : '' ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?= $contentOpen ? 'true' : 'false' ?>" aria-controls="contentSubmenu">
                <span><i class="bi bi-folder2-open"></i> <span>Contenu</span></span>
                <i class="bi bi-chevron-down small nav-collapse-caret"></i>
            </a>
            <div class="collapse <?= $contentOpen ? 'show' : '' ?> nav-submenu" id="contentSubmenu">
                <?= navLink('programs', '/admin/programs/list.php', 'bi-mortarboard', 'Filières', $currentDir) ?>
                <?= navLink('partners', '/admin/partners/list.php', 'bi-globe2', 'Partenaires', $currentDir) ?>
                <?= navLink('news', '/admin/news/list.php', 'bi-newspaper', 'Actualités', $currentDir) ?>
                <?= navLink('press', '/admin/press/list.php', 'bi-globe', "HITAS dans l'actu internationale", $currentDir) ?>
                <?= navLink('team', '/admin/team/list.php', 'bi-people', 'Équipe', $currentDir) ?>
                <?= navLink('testimonials', '/admin/testimonials/list.php', 'bi-chat-quote', 'Témoignages', $currentDir) ?>
                <?= navLink('faq', '/admin/faq/list.php', 'bi-question-circle', 'FAQ', $currentDir) ?>
                <?= navLink('pages', '/admin/pages/list.php', 'bi-file-text', 'Pages statiques', $currentDir) ?>
                <?= navLink('campus_highlights', '/admin/campus_highlights/list.php', 'bi-geo-alt', 'Campus (page À propos)', $currentDir) ?>
                <?= navLink('page_galleries', '/admin/page_galleries/hub.php', 'bi-collection', 'Diaporamas (À propos, Mission, Admission)', $currentDir) ?>
            </div>
            <?= navLink('gallery', '/admin/gallery/list.php', 'bi-images', 'Galerie & Médiathèque', $currentDir) ?>

            <div class="nav-section">Gestion</div>
            <?= navLink('navigation', '/admin/navigation/list.php', 'bi-signpost-2', 'Navigation', $currentDir) ?>
            <?= navLink('seo', '/admin/seo/list.php', 'bi-search', 'SEO', $currentDir) ?>
            <?= navLink('messages', '/admin/messages/list.php', 'bi-envelope', 'Messages reçus', $currentDir) ?>
            <?= navLink('settings', '/admin/settings/index.php', 'bi-gear', 'Paramètres', $currentDir) ?>
            <?= navLink('users', '/admin/users/list.php', 'bi-person-badge', 'Utilisateurs admin', $currentDir) ?>
        <?php endif; ?>
    </nav>
</aside>
