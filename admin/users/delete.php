<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/users/list.php');
    exit;
}
checkCsrf();

$id = (int)($_POST['id'] ?? 0);

if ($id === (int)$_SESSION['admin_id']) {
    $_SESSION['flash_error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
    header('Location: ' . BASE_URL . '/admin/users/list.php');
    exit;
}

$pdo = getDB();
$pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);

$_SESSION['flash_success'] = 'Utilisateur supprimé.';
header('Location: ' . BASE_URL . '/admin/users/list.php');
exit;
