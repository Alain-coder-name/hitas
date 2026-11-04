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

// '__none__' = l'administrateur a explicitement retiré l'image : la page
// utilisera l'arrière-plan de secours CSS, même si une image par défaut existe.
$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
$stmt->execute(['pagebg_' . $pageKey, '__none__']);

$_SESSION['flash_success'] = 'Arrière-plan supprimé pour « ' . $defs[$pageKey]['label'] . ' ». Le site utilise désormais un arrière-plan de secours.';
header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
exit;
