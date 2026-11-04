<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/media_library.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}
checkCsrf();

$folder   = trim($_POST['folder'] ?? '');
$filename = trim($_POST['filename'] ?? '');
$target   = trim($_POST['target'] ?? '');

if (!mediaIsSafeName($folder) || !mediaIsSafeName($filename) || $target === '') {
    $_SESSION['flash_error'] = 'Requête invalide.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$fullPath = UPLOAD_PATH . $folder . '/' . $filename;
if (!is_file($fullPath)) {
    $_SESSION['flash_error'] = 'Ce fichier n\'existe plus.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$parts = explode('|', $target);
$pdo = getDB();
$ok = false;
$redirectUrl = BASE_URL . '/admin/gallery/media.php';

if ($parts[0] === 'table' && count($parts) === 4) {
    [, $table, $column, $recordId] = $parts;
    $recordId = (int)$recordId;

    // N'accepte que des emplacements réellement déclarés par l'application
    // (empêche une requête forgée de cibler une table/colonne arbitraire).
    $source = mediaFindTableSource($table, $column);
    if ($source && $recordId > 0) {
        $stmt = $pdo->prepare("UPDATE `{$table}` SET `{$column}` = ? WHERE id = ?");
        $stmt->execute([$filename, $recordId]);
        $ok = $stmt->rowCount() > 0;
    }
} elseif ($parts[0] === 'settings' && count($parts) === 2) {
    [, $settingKey] = $parts;

    $source = mediaFindSettingsSource($settingKey);
    if ($source) {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([$settingKey, $filename]);
        $ok = true;
    }
} elseif ($parts[0] === 'create' && count($parts) === 3) {
    [, $table, $column] = $parts;

    // On n'autorise la création que sur une source qui déclare explicitement
    // les champs à demander (createFields) : impossible de créer une fiche
    // dans une table arbitraire depuis une requête forgée.
    $source = mediaFindTableSource($table, $column);
    $createFields = $source['createFields'] ?? null;

    if ($source && $createFields) {
        $insertColumns = [];
        $insertValues  = [];
        $missing = [];
        $pageKeyValue = null;

        foreach ($createFields as $field) {
            $value = trim($_POST['create_' . $field['column']] ?? '');

            if ($field['type'] === 'select') {
                $options = $field['options'] ?? [];
                if (!array_key_exists($value, $options)) {
                    $missing[] = $field['label'];
                    continue;
                }
            } elseif (!empty($field['required']) && $value === '') {
                $missing[] = $field['label'];
                continue;
            }

            $insertColumns[] = $field['column'];
            $insertValues[]  = $value;
            if ($field['column'] === 'page_key') {
                $pageKeyValue = $value;
            }
        }

        if ($missing) {
            $_SESSION['flash_error'] = 'Champ(s) obligatoire(s) manquant(s) : ' . implode(', ', $missing) . '.';
            header('Location: ' . BASE_URL . '/admin/gallery/media.php');
            exit;
        }

        $insertColumns[] = $column; // la colonne image/logo/photo elle-même
        $insertValues[]  = $filename;

        // Contenu créé depuis la médiathèque = brouillon par défaut (sauf
        // page_galleries, qui n'a pas de notion de publication : ce sont de
        // simples photos additives pour un diaporama).
        if ($table !== 'page_galleries') {
            $insertColumns[] = 'is_published';
            $insertValues[]  = 0;
        }

        if ($table === 'news') {
            $titleValue = $_POST['create_title'] ?? '';
            $insertColumns[] = 'slug';
            $insertValues[]  = uniqueSlug($pdo, 'news', slugify($titleValue));
        }

        if ($table === 'page_galleries') {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_galleries WHERE page_key = ?');
            $stmt->execute([$pageKeyValue]);
            $insertColumns[] = 'sort_order';
            $insertValues[]  = (int)$stmt->fetchColumn();
        }

        $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
        $columnsSql = implode(',', array_map(fn($c) => "`{$c}`", $insertColumns));
        $stmt = $pdo->prepare("INSERT INTO `{$table}` ({$columnsSql}) VALUES ({$placeholders})");
        $stmt->execute($insertValues);
        $newId = (int)$pdo->lastInsertId();
        $ok = true;

        if ($table === 'page_galleries') {
            $redirectUrl = BASE_URL . sprintf($source['editUrl'], $pageKeyValue);
        } elseif (!empty($source['editUrl']) && strpos($source['editUrl'], '%d') !== false) {
            $redirectUrl = BASE_URL . sprintf($source['editUrl'], $newId);
        }
    }
}

if ($ok) {
    $_SESSION['flash_success'] = 'Fichier « ' . $filename . ' » réutilisé avec succès'
        . ($redirectUrl !== BASE_URL . '/admin/gallery/media.php' ? ' — complétez la fiche ci-dessous et publiez-la quand elle est prête.' : ' — il est de nouveau actif sur le site.');
} else {
    $_SESSION['flash_error'] = 'Emplacement de réutilisation invalide, ou l\'élément n\'existe plus.';
    $redirectUrl = BASE_URL . '/admin/gallery/media.php';
}

header('Location: ' . $redirectUrl);
exit;
