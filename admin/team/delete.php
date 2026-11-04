<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/team/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);

$pdo->prepare('DELETE FROM team_members WHERE id = ?')->execute([$id]);

$_SESSION['flash_success'] = 'Membre supprimé.';
header('Location: ' . BASE_URL . '/admin/team/list.php');
exit;
