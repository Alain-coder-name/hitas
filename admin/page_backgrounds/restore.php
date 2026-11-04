<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$defs = pageBackgroundDefs();

$pageKey = trim($_POST['page_key'] ?? '');
if (!isset($defs[$pageKey])) {
    $_SESSION['flash_error'] = 'Page inconnue.';
    header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
    exit;
}

// Supprime le fichier personnalisé du disque s'il existe.
$previous = getSetting('pagebg_' . $pageKey, '');
if ($previous !== '' && $previous !== '__none__') {
    $oldPath = UPLOAD_PATH . 'pagebg/' . $previous;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

// On retire simplement la ligne de settings : la page retombe alors
// automatiquement sur son image par défaut (voir getPageBackgroundInfo()).
$stmt = $pdo->prepare('DELETE FROM settings WHERE setting_key = ?');
$stmt->execute(['pagebg_' . $pageKey]);

$_SESSION['flash_success'] = 'Image par défaut restaurée pour « ' . $defs[$pageKey]['label'] . ' ».';
header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
exit;
