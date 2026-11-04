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

// Sécurité : la clé de page doit obligatoirement faire partie de la liste connue.
$pageKey = trim($_POST['page_key'] ?? '');
if (!isset($defs[$pageKey])) {
    $_SESSION['flash_error'] = 'Page inconnue.';
    header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
    exit;
}

if (empty($_FILES['background_image']['name']) || $_FILES['background_image']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Merci de sélectionner une image.';
    header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
    exit;
}

// Limite de taille raisonnable (5 Mo).
$maxBytes = 5 * 1024 * 1024;
if ($_FILES['background_image']['size'] > $maxBytes) {
    $_SESSION['flash_error'] = 'Image trop volumineuse (5 Mo maximum).';
    header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
    exit;
}

// handleImageUpload() vérifie déjà le type de fichier (jpg/jpeg/png/webp),
// génère un nom de fichier sûr (uniqid) et crée le dossier de destination.
$filename = handleImageUpload('background_image', 'pagebg');

if ($filename) {
    // Si une image personnalisée existait déjà pour cette page, on la supprime
    // du disque pour ne pas accumuler de fichiers orphelins.
    $previous = getSetting('pagebg_' . $pageKey, '');
    if ($previous !== '' && $previous !== '__none__') {
        $oldPath = UPLOAD_PATH . 'pagebg/' . $previous;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute(['pagebg_' . $pageKey, $filename]);

    $_SESSION['flash_success'] = 'Arrière-plan mis à jour pour « ' . $defs[$pageKey]['label'] . ' ».';
}

header('Location: ' . BASE_URL . '/admin/page_backgrounds/index.php');
exit;
