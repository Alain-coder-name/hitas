<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/media_library.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}
checkCsrf();

$folder = trim($_POST['folder'] ?? '');

if (!mediaIsSafeName($folder) || !array_key_exists($folder, mediaKnownFolders())) {
    $_SESSION['flash_error'] = 'Dossier invalide.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

if (empty($_FILES['new_file']['name']) || $_FILES['new_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Aucun fichier reçu.';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$ext = strtolower(pathinfo($_FILES['new_file']['name'], PATHINFO_EXTENSION));
$allowed = array_merge(mediaImageExtensions(), mediaVideoExtensions());

if (!in_array($ext, $allowed, true)) {
    $_SESSION['flash_error'] = 'Format non autorisé (' . implode(', ', $allowed) . ' uniquement).';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$kind = in_array($ext, mediaVideoExtensions(), true) ? 'video' : 'image';
if (!is_uploaded_file($_FILES['new_file']['tmp_name']) || !uploadContentIsValid($_FILES['new_file']['tmp_name'], $ext, $kind)) {
    hitasSecurityLog('médiathèque : fichier refusé (contenu invalide) ' . oneLine($_FILES['new_file']['name'], 60));
    $_SESSION['flash_error'] = 'Ce fichier n\'est pas une vraie ' . ($kind === 'video' ? 'vidéo MP4/WebM' : 'image') . ' (ou dépasse 30 Mo pour une image).';
    header('Location: ' . BASE_URL . '/admin/gallery/media.php');
    exit;
}

$destDir = UPLOAD_PATH . $folder . '/';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

$filename = $folder . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

if (move_uploaded_file($_FILES['new_file']['tmp_name'], $destDir . $filename)) {
    $_SESSION['flash_success'] = 'Fichier « ' . $filename . ' » ajouté à la médiathèque.';
} else {
    $_SESSION['flash_error'] = 'L\'upload a échoué (permissions serveur ?).';
}

header('Location: ' . BASE_URL . '/admin/gallery/media.php');
exit;
