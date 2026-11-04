<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/page_galleries/hub.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$key = trim($_POST['key'] ?? '');

$pdo->prepare('DELETE FROM page_galleries WHERE id = ? AND page_key = ?')->execute([$id, $key]);

$_SESSION['flash_success'] = 'Photo supprimée.';
header('Location: ' . BASE_URL . '/admin/page_galleries/list.php?key=' . urlencode($key));
exit;
