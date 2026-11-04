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

if (!mediaIsSafeName($folder) || !mediaIsSafeName($filename)) {
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

if (empty($_FILES['new_file']['name']) || $_FILES['new_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Aucun fichier reçu.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$currentKind = mediaFileKind($filename);
$allowed = $currentKind === 'video' ? mediaVideoExtensions() : mediaImageExtensions();
$newExt = strtolower(pathinfo($_FILES['new_file']['name'], PATHINFO_EXTENSION));

if (!in_array($newExt, $allowed, true)) {
    $_SESSION['flash_error'] = 'Format non autorisé pour remplacer ce fichier (' . implode(', ', $allowed) . ' attendu).';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

if (!is_uploaded_file($_FILES['new_file']['tmp_name']) || !uploadContentIsValid($_FILES['new_file']['tmp_name'], $newExt, $currentKind === 'video' ? 'video' : 'image')) {
    hitasSecurityLog('médiathèque : remplacement refusé (contenu invalide) ' . oneLine($_FILES['new_file']['name'], 60));
    $_SESSION['flash_error'] = 'Ce fichier n\'est pas valide (le contenu ne correspond pas à son format).';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

// On écrase le fichier EXISTANT en conservant exactement le même nom et le
// même emplacement : aucune ligne de la base de données n'a besoin d'être
// modifiée, toutes les pages publiques qui l'affichent sont donc mises à
// jour automatiquement dès l'upload.
if (move_uploaded_file($_FILES['new_file']['tmp_name'], $fullPath)) {
    $usages = mediaFindUsages($folder, $filename);
    $_SESSION['flash_success'] = 'Fichier « ' . $filename . ' » remplacé avec succès'
        . (!empty($usages) ? ' (mis à jour automatiquement à ' . count($usages) . ' endroit(s)).' : '.');
} else {
    $_SESSION['flash_error'] = 'Le remplacement a échoué (permissions serveur ?).';
}

header('Location: ' . BASE_URL . '/admin/gallery/media.php');
exit;
