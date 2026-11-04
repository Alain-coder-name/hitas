<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/home/index.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$fields = [
    'home_hero_eyebrow','home_hero_title','home_hero_lead','home_hero_cta_text','home_hero_cta_link',
    'home_pillar_1_title','home_pillar_1_text','home_pillar_2_title','home_pillar_2_text','home_pillar_3_title','home_pillar_3_text',
    'home_cta_title','home_cta_subtitle','home_cta_btn1_text','home_cta_btn1_link'
];

$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
foreach ($fields as $f) {
    $stmt->execute([$f, trim($_POST[$f] ?? '')]);
}

$_SESSION['flash_success'] = 'Contenu d\'accueil mis à jour.';
header('Location: ' . BASE_URL . '/admin/home/index.php');
exit;
