<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/navigation/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$label = trim($_POST['label'] ?? '');
$url = trim($_POST['url'] ?? '');
$isVisible = (int)($_POST['is_visible'] ?? 0);

if ($label === '' || $url === '') {
    $_SESSION['flash_error'] = 'Le libellé et le lien sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/navigation/list.php');
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE nav_menu SET label=?, url=?, is_visible=? WHERE id=?');
    $stmt->execute([$label, $url, $isVisible, $id]);
} else {
    $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(order_index), 0) FROM nav_menu')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO nav_menu (label, url, order_index, is_visible) VALUES (?,?,?,?)');
    $stmt->execute([$label, $url, $maxOrder + 1, $isVisible]);
}

$_SESSION['flash_success'] = 'Menu mis à jour avec succès.';
header('Location: ' . BASE_URL . '/admin/navigation/list.php');
exit;
