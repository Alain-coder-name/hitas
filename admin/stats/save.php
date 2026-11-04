<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/stats/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id       = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$label    = trim($_POST['label'] ?? '');
$value    = trim($_POST['value'] ?? '');
$icon     = trim($_POST['icon'] ?? '') ?: null;
$isActive = !empty($_POST['is_active']) ? 1 : 0;

if ($label === '' || $value === '') {
    $_SESSION['flash_error'] = 'La valeur et le libellé sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/stats/' . ($id ? 'form.php?id=' . $id : 'form.php'));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE stats SET label=?, value=?, icon=?, is_active=? WHERE id=?');
    $stmt->execute([$label, $value, $icon, $isActive, $id]);
    $_SESSION['flash_success'] = 'Chiffre clé mis à jour avec succès.';
} else {
    $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM stats')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO stats (label, value, icon, sort_order, is_active) VALUES (?,?,?,?,?)');
    $stmt->execute([$label, $value, $icon, $maxOrder + 1, $isActive]);
    $_SESSION['flash_success'] = 'Chiffre clé ajouté avec succès.';
}

header('Location: ' . BASE_URL . '/admin/stats/list.php');
exit;
