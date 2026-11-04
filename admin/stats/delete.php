<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/stats/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$pdo->prepare('DELETE FROM stats WHERE id = ?')->execute([$id]);

$_SESSION['flash_success'] = 'Chiffre clé supprimé.';
header('Location: ' . BASE_URL . '/admin/stats/list.php');
exit;
