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

// --- Vérification d'usage avant suppression : on retrace les références
// dans TOUTES les tables susceptibles de pointer vers ce fichier. Tant
// qu'une seule référence existe, la suppression est refusée pour ne
// jamais casser une page publique (image cassée).
$usages = mediaFindUsages($folder, $filename);

if (!empty($usages)) {
    $labels = array_map(function ($u) {
        return $u['label'] . ($u['title'] ? ' (' . $u['title'] . ')' : '');
    }, $usages);

    $_SESSION['flash_error'] = 'Impossible de supprimer « ' . $filename . ' » : ce fichier est encore utilisé à '
        . count($usages) . ' endroit(s) — ' . implode(', ', $labels)
        . '. Retirez-le d\'abord de ces contenus (ou remplacez-le) avant de le supprimer.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

if (@unlink($fullPath)) {
    $_SESSION['flash_success'] = 'Fichier « ' . $filename . ' » supprimé définitivement.';
} else {
    $_SESSION['flash_error'] = 'La suppression a échoué (permissions serveur ?).';
}

header('Location: ' . BASE_URL . '/admin/gallery/media.php');
exit;
