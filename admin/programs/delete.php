<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/programs/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM programs WHERE id = ?');
$stmt->execute([$id]);

$_SESSION['flash_success'] = 'Filière supprimée.';
header('Location: ' . BASE_URL . '/admin/programs/list.php');
exit;
