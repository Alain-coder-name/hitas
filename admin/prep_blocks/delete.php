<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/prep_blocks/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$pdo->prepare('DELETE FROM prep_blocks WHERE id = ?')->execute([$id]);

$_SESSION['flash_success'] = 'Bloc de mise à niveau supprimé.';
header('Location: ' . BASE_URL . '/admin/prep_blocks/list.php');
exit;
