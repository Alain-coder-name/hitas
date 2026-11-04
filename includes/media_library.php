<?php
// =========================================================
// Médiathèque — bibliothèque de fonctions partagées (Lot C)
// -----------------------------------------------------------
// Donne une vue unifiée de TOUS les fichiers présents dans
// uploads/*, quelle que soit la table qui les utilise, et permet
// de savoir en un coup d'œil si un fichier est utilisé quelque
// part avant de le supprimer ou de le remplacer.
// =========================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/site_helpers.php';

/**
 * Description des dossiers d'upload connus de l'application.
 * Clé = nom du sous-dossier physique dans uploads/, valeur = libellé humain.
 * Le scan du disque (mediaScanFiles) reste dynamique : si un nouveau
 * dossier apparaît un jour dans uploads/, il sera quand même listé
 * (avec son nom brut comme libellé).
 */
function mediaKnownFolders(): array
{
    return [
        'programs'          => 'Programmes',
        'news'              => 'Actualités',
        'pages'             => 'Pages statiques',
        'partners'          => 'Partenaires',
        'providers'         => 'Anciens fichiers (fusionnés avec Partenaires)',
        'team'              => 'Équipe',
        'testimonials'      => 'Témoignages',
        'gallery'           => 'Galerie',
        'hero'              => 'Diaporama accueil',
        'campus_highlights' => 'Campus / temps forts',
        'pagebg'            => 'Arrière-plans de page',
        'page_galleries'    => 'Galeries par page',
        'press'             => 'Presse / actu internationale',
        'settings'          => 'Paramètres généraux',
    ];
}

/**
 * Carte complète des endroits de la base de données susceptibles de
 * référencer un fichier d'upload. C'est LE point central à mettre à jour
 * si un jour un nouveau module ajoute un champ image/logo/vidéo.
 *
 * kind = 'table'    -> une colonne d'une table classique contient le nom de fichier
 * kind = 'settings' -> une ligne de la table clé/valeur `settings` contient le nom de fichier
 */
function mediaFileSources(): array
{
    $sources = [
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'programs',       'column' => 'image',    'folder' => 'programs',     'titleColumn' => 'title',     'label' => 'Programme — image',            'editUrl' => '/admin/programs/form.php?id=%d'],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'programs',       'column' => 'og_image', 'folder' => 'programs',     'titleColumn' => 'title',     'label' => 'Programme — image SEO (og:image)', 'editUrl' => '/admin/seo/edit.php?type=program&id=%d'],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'news',           'column' => 'image',    'folder' => 'news',         'titleColumn' => 'title',     'label' => 'Actualité — image',            'editUrl' => '/admin/news/form.php?id=%d',
            'createFields' => [['column' => 'title', 'label' => 'Titre de l\'actualité', 'type' => 'text', 'required' => true]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'news',           'column' => 'og_image', 'folder' => 'news',         'titleColumn' => 'title',     'label' => 'Actualité — image SEO (og:image)', 'editUrl' => '/admin/seo/edit.php?type=news&id=%d'],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'pages',          'column' => 'image',    'folder' => 'pages',        'titleColumn' => 'title',     'label' => 'Page statique — image',        'editUrl' => '/admin/pages/edit.php?id=%d'],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'pages',          'column' => 'og_image', 'folder' => 'pages',        'titleColumn' => 'title',     'label' => 'Page statique — image SEO (og:image)', 'editUrl' => '/admin/seo/edit.php?type=page&id=%d'],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'partners',       'column' => 'logo',     'folder' => 'partners',     'titleColumn' => 'name',      'label' => 'Partenaire — logo',            'editUrl' => '/admin/partners/form.php?id=%d',
            'createFields' => [
                ['column' => 'name',    'label' => 'Nom de l\'institution partenaire', 'type' => 'text', 'required' => true],
                ['column' => 'country', 'label' => 'Pays',                            'type' => 'text', 'required' => true],
            ]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'team_members',   'column' => 'photo',    'folder' => 'team',         'titleColumn' => 'full_name', 'label' => 'Équipe — photo',               'editUrl' => '/admin/team/form.php?id=%d',
            'createFields' => [['column' => 'full_name', 'label' => 'Nom complet', 'type' => 'text', 'required' => true]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'testimonials',   'column' => 'photo',    'folder' => 'testimonials', 'titleColumn' => 'full_name', 'label' => 'Témoignage — photo',           'editUrl' => '/admin/testimonials/form.php?id=%d',
            'createFields' => [
                ['column' => 'full_name', 'label' => 'Nom complet',          'type' => 'text',     'required' => true],
                ['column' => 'content',   'label' => 'Texte du témoignage',  'type' => 'textarea', 'required' => true],
            ]],
        ['kind' => 'table', 'fileKind' => 'video', 'table' => 'testimonials',   'column' => 'video',    'folder' => 'testimonials', 'titleColumn' => 'full_name', 'label' => 'Témoignage — vidéo',           'editUrl' => '/admin/testimonials/form.php?id=%d',
            'createFields' => [
                ['column' => 'full_name', 'label' => 'Nom complet',          'type' => 'text',     'required' => true],
                ['column' => 'content',   'label' => 'Texte du témoignage',  'type' => 'textarea', 'required' => true],
            ]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'gallery',        'column' => 'image',    'folder' => 'gallery',      'titleColumn' => 'title',     'label' => 'Galerie — photo',              'editUrl' => '/admin/gallery/form.php?id=%d',
            'createFields' => [['column' => 'title', 'label' => 'Titre de la photo', 'type' => 'text', 'required' => true]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'hero_slides',    'column' => 'image',    'folder' => 'hero',         'titleColumn' => 'title',     'label' => 'Diaporama accueil — image',    'editUrl' => '/admin/hero_slides/form.php?id=%d',
            'createFields' => [['column' => 'title', 'label' => 'Titre (facultatif)', 'type' => 'text', 'required' => false]]],
        ['kind' => 'table', 'fileKind' => 'video', 'table' => 'hero_slides',    'column' => 'video',    'folder' => 'hero',         'titleColumn' => 'title',     'label' => 'Diaporama accueil — vidéo',    'editUrl' => '/admin/hero_slides/form.php?id=%d',
            'createFields' => [['column' => 'title', 'label' => 'Titre (facultatif)', 'type' => 'text', 'required' => false]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'campus_highlights', 'column' => 'photo', 'folder' => 'campus_highlights', 'titleColumn' => 'name', 'label' => 'Campus / temps forts — photo', 'editUrl' => '/admin/campus_highlights/form.php?id=%d',
            'createFields' => [['column' => 'name', 'label' => 'Nom du lieu / campus', 'type' => 'text', 'required' => true]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'page_galleries', 'column' => 'image',    'folder' => 'page_galleries', 'titleColumn' => 'page_key', 'label' => 'Galerie par page — photo', 'editUrl' => '/admin/page_galleries/list.php?key=%s',
            'createFields' => [['column' => 'page_key', 'label' => 'Page', 'type' => 'select', 'required' => true, 'options' => [
                'a-propos'            => 'À propos',
                'mission'             => 'Notre mission',
                'admission-conditions'=> "Conditions d'admission (FAQ)",
            ]]]],
        ['kind' => 'table', 'fileKind' => 'image', 'table' => 'press_mentions', 'column' => 'image',    'folder' => 'press',        'titleColumn' => 'title',     'label' => 'Presse — image',               'editUrl' => '/admin/press/form.php?id=%d',
            'createFields' => [['column' => 'title', 'label' => 'Titre de la mention de presse', 'type' => 'text', 'required' => true]]],
        ['kind' => 'settings', 'fileKind' => 'image', 'settingKey' => 'favicon', 'folder' => 'settings', 'label' => 'Paramètres — favicon', 'editUrl' => '/admin/settings/index.php'],
    ];

    // Un arrière-plan personnalisable par page (stocké en `settings`, une
    // clé « pagebg_{page} » par page) : une source par page connue.
    foreach (pageBackgroundDefs() as $key => $def) {
        $sources[] = [
            'kind' => 'settings', 'fileKind' => 'image',
            'settingKey' => 'pagebg_' . $key,
            'folder' => 'pagebg',
            'label' => 'Arrière-plan de page — ' . $def['label'],
            'editUrl' => '/admin/page_backgrounds/index.php',
        ];
    }

    return $sources;
}

/**
 * Retrouve, parmi les sources connues, celle qui correspond exactement à
 * une table + colonne. Sert à valider côté serveur qu'une demande de
 * réutilisation d'un fichier orphelin cible bien un emplacement réel de
 * l'application (protection contre une requête forgée).
 */
function mediaFindTableSource(string $table, string $column): ?array
{
    foreach (mediaFileSources() as $src) {
        if ($src['kind'] === 'table' && $src['table'] === $table && $src['column'] === $column) {
            return $src;
        }
    }
    return null;
}

/** Même principe que mediaFindTableSource(), pour une source de type `settings`. */
function mediaFindSettingsSource(string $settingKey): ?array
{
    foreach (mediaFileSources() as $src) {
        if ($src['kind'] === 'settings' && $src['settingKey'] === $settingKey) {
            return $src;
        }
    }
    return null;
}

/**
 * Pour un type de fichier donné (image ou vidéo), liste TOUS les
 * emplacements du site qui peuvent recevoir ce type de fichier — pas
 * seulement ceux du dossier d'origine du fichier orphelin : le dossier de
 * stockage n'est qu'un détail d'organisation, un logo envoyé dans un
 * dossier peut très bien devenir une photo d'équipe ailleurs. On inclut
 * aussi les emplacements qui ont déjà un fichier (marqués « occupied » pour
 * que l'admin sache qu'il le remplacera) : par exemple, les photos de la
 * Galerie pointent souvent toutes vers une image "à remplacer" commune.
 */
function mediaReusableTargets(string $fileKind): array
{
    $pdo = getDB();
    $groups = [];
    $settingsOptions = [];

    foreach (mediaFileSources() as $src) {
        if ($src['fileKind'] !== $fileKind) {
            continue;
        }

        if ($src['kind'] === 'table') {
            $table = $src['table'];
            $column = $src['column'];
            $titleColumn = $src['titleColumn'];
            $createFields = $src['createFields'] ?? null;
            $sql = "SELECT id, `{$titleColumn}` AS ttl, `{$column}` AS cur FROM `{$table}` ORDER BY `{$titleColumn}`";

            try {
                $rows = $pdo->query($sql)->fetchAll();
            } catch (PDOException $e) {
                continue;
            }

            // On garde le groupe même sans aucune fiche existante, tant qu'on
            // peut en créer une nouvelle directement depuis la médiathèque.
            if (!$rows && !$createFields) {
                continue;
            }

            $options = [];
            foreach ($rows as $row) {
                $options[] = [
                    'value'    => 'table|' . $table . '|' . $column . '|' . $row['id'],
                    'label'    => ($row['ttl'] !== null && $row['ttl'] !== '') ? $row['ttl'] : ('#' . $row['id']),
                    'occupied' => ($row['cur'] !== null && $row['cur'] !== ''),
                ];
            }

            $createValue = null;
            if ($createFields) {
                $createValue = 'create|' . $table . '|' . $column;
                $options[] = [
                    'value'    => $createValue,
                    'label'    => '+ Créer une nouvelle fiche avec cette image',
                    'occupied' => false,
                ];
            }

            $groups[] = [
                'label'        => $src['label'],
                'options'      => $options,
                'createFields' => $createFields,
                'createValue'  => $createValue,
            ];
            continue;
        }

        if ($src['kind'] === 'settings') {
            $value = getSetting($src['settingKey'], '');
            $settingsOptions[] = [
                'value'    => 'settings|' . $src['settingKey'],
                'label'    => $src['label'],
                'occupied' => ($value !== '' && $value !== '__none__'),
            ];
        }
    }

    if ($settingsOptions) {
        $groups[] = ['label' => 'Paramètres du site', 'options' => $settingsOptions];
    }

    return $groups;
}

/**
 * Construit l'index complet "dossier/fichier" => [usages...] en une seule
 * passe de requêtes (une par source), pour éviter le N+1 quand on liste
 * des dizaines de fichiers.
 */
function mediaBuildUsageIndex(): array
{
    $pdo = getDB();
    $index = [];

    foreach (mediaFileSources() as $src) {
        if ($src['kind'] === 'settings') {
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
            $stmt->execute([$src['settingKey']]);
            foreach ($stmt->fetchAll() as $row) {
                $key = $src['folder'] . '/' . $row['setting_value'];
                $index[$key][] = [
                    'label'    => $src['label'],
                    'title'    => null,
                    'editUrl'  => BASE_URL . $src['editUrl'],
                ];
            }
            continue;
        }

        // kind === 'table'
        $table  = $src['table'];
        $column = $src['column'];
        $titleColumn = $src['titleColumn'];
        $sql = "SELECT id, `{$column}` AS fname, `{$titleColumn}` AS ttl FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` != ''";
        try {
            $rows = $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            // Table absente de la base (module pas encore installé) : on
            // ignore simplement cette source plutôt que de planter la médiathèque.
            continue;
        }
        foreach ($rows as $row) {
            $key = $src['folder'] . '/' . $row['fname'];
            $index[$key][] = [
                'label'   => $src['label'],
                'title'   => $row['ttl'],
                'editUrl' => BASE_URL . sprintf($src['editUrl'], (int)$row['id']),
            ];
        }
    }

    return $index;
}

/** Extensions reconnues comme image / vidéo pour l'affichage et les contrôles de remplacement. */
function mediaImageExtensions(): array { return ['jpg', 'jpeg', 'png', 'webp', 'gif']; }
function mediaVideoExtensions(): array { return ['mp4', 'webm']; }

function mediaFileKind(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, mediaImageExtensions(), true)) return 'image';
    if (in_array($ext, mediaVideoExtensions(), true)) return 'video';
    return 'other';
}

/**
 * Parcourt le disque (uploads/*) et retourne la liste de tous les fichiers,
 * enrichis avec leurs usages (issus de l'index base de données).
 *
 * Chaque entrée : folder, filename, path (clé unique), url, size, mtime,
 * kind (image/video/other), usages (array), isOrphan (bool).
 */
function mediaScanFiles(): array
{
    $files = [];
    $index = mediaBuildUsageIndex();
    $folders = mediaKnownFolders();

    // On part des dossiers connus ET de tout ce qui existe réellement sur le
    // disque, au cas où un dossier supplémentaire aurait été créé à la main.
    $diskFolders = [];
    if (is_dir(UPLOAD_PATH)) {
        foreach (scandir(UPLOAD_PATH) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (is_dir(UPLOAD_PATH . $entry)) {
                $diskFolders[$entry] = true;
            }
        }
    }
    $allFolders = array_unique(array_merge(array_keys($folders), array_keys($diskFolders)));

    foreach ($allFolders as $folder) {
        $dir = UPLOAD_PATH . $folder . '/';
        if (!is_dir($dir)) continue;

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.htaccess') continue;
            $fullPath = $dir . $entry;
            if (!is_file($fullPath)) continue;

            $key = $folder . '/' . $entry;
            $usages = $index[$key] ?? [];

            $files[] = [
                'folder'    => $folder,
                'folderLabel' => $folders[$folder] ?? ucfirst($folder),
                'filename'  => $entry,
                'key'       => $key,
                'url'       => UPLOAD_URL . $folder . '/' . rawurlencode($entry),
                'size'      => filesize($fullPath),
                'mtime'     => filemtime($fullPath),
                'kind'      => mediaFileKind($entry),
                'usages'    => $usages,
                'isOrphan'  => empty($usages),
            ];
        }
    }

    // Les plus récents en premier
    usort($files, function ($a, $b) { return $b['mtime'] <=> $a['mtime']; });

    return $files;
}

/** Usages d'un fichier précis (dossier + nom), pour un contrôle ponctuel avant suppression/remplacement. */
function mediaFindUsages(string $folder, string $filename): array
{
    $index = mediaBuildUsageIndex();
    return $index[$folder . '/' . $filename] ?? [];
}

function mediaHumanSize(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' o';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' Ko';
    return round($bytes / (1024 * 1024), 1) . ' Mo';
}

/**
 * Empêche les chemins fournis par l'utilisateur (dossier / nom de fichier)
 * de sortir de uploads/ (protection contre la traversée de répertoires).
 */
function mediaIsSafeName(string $name): bool
{
    if ($name === '' || $name === '.' || $name === '..') return false;
    if (strpos($name, '/') !== false || strpos($name, '\\') !== false) return false;
    if (strpos($name, "\0") !== false) return false;
    return true;
}
