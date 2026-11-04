<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/navigation/list.php');
    exit;
}
checkCsrf();

$id = (int)($_POST['id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare('DELETE FROM nav_menu WHERE id = ?');
$stmt->execute([$id]);

$_SESSION['flash_success'] = 'Lien supprimé.';
header('Location: ' . BASE_URL . '/admin/navigation/list.php');
exit;
