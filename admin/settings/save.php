<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/settings/index.php');
    exit;
}
checkCsrf();

$pdo = getDB();

$fields = ['site_name', 'site_slogan', 'primary_color', 'accent_color', 'ivory_color', 'contact_email', 'contact_phone', 'call_phone', 'floating_whatsapp_numbers', 'applications_email', 'applications_whatsapp', 'address', 'po_box', 'maps_query', 'facebook_url', 'linkedin_url', 'instagram_url', 'tiktok_url', 'whatsapp_channel_url', 'hitas_tv_url', 'lightbox_delay'];
$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

foreach ($fields as $field) {
    $value = trim($_POST[$field] ?? '');
    if ($field === 'lightbox_delay') {
        $value = (string)max(2, min(60, (int)$value ?: 4));
    }
    $stmt->execute([$field, $value]);
}

// Favicon : upload manuel (formats acceptés plus larges que handleImageUpload)
if (!empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
    // SVG retiré : un fichier SVG peut contenir du JavaScript (faille XSS).
    $allowed = ['png', 'ico'];
    $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed, true) && is_uploaded_file($_FILES['favicon']['tmp_name']) && uploadContentIsValid($_FILES['favicon']['tmp_name'], $ext, 'favicon')) {
        $filename = 'favicon_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destDir = UPLOAD_PATH . 'settings/';
        if (!is_dir($destDir)) { mkdir($destDir, 0755, true); }
        move_uploaded_file($_FILES['favicon']['tmp_name'], $destDir . $filename);
        $stmt->execute(['favicon', $filename]);
    } else {
        $_SESSION['flash_error'] = 'Favicon refusé (fichier png ou ico valide uniquement). Les autres paramètres ont bien été enregistrés.';
    }
}

if (empty($_SESSION['flash_error'])) {
    $_SESSION['flash_success'] = 'Paramètres mis à jour avec succès.';
}
header('Location: ' . BASE_URL . '/admin/settings/index.php');
exit;
