<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/banners/index.php');
    exit;
}
checkCsrf();

$pdo = getDB();

$pageKeys = ['programmes', 'partenaires', 'equipe', 'actualites', 'postulez'];
$fields = [];
foreach ($pageKeys as $key) {
    $fields[] = $key . '_hero_eyebrow';
    $fields[] = $key . '_hero_title';
    $fields[] = $key . '_hero_lead';
}

$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
foreach ($fields as $f) {
    if (array_key_exists($f, $_POST)) {
        $stmt->execute([$f, trim($_POST[$f])]);
    }
}

$_SESSION['flash_success'] = 'Bandeaux mis à jour.';
header('Location: ' . BASE_URL . '/admin/banners/index.php');
exit;
