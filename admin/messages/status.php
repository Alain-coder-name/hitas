<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/messages/list.php');
    exit;
}
checkCsrf();

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? 'non_traite';
$allowed = ['non_traite', 'en_cours', 'traite'];
if (!in_array($status, $allowed, true)) {
    $status = 'non_traite';
}

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE contact_messages SET status = ?, is_read = 1 WHERE id = ?');
$stmt->execute([$status, $id]);

$redirect = $_POST['redirect'] ?? ('/admin/messages/view.php?id=' . $id);
$_SESSION['flash_success'] = 'Statut du message mis à jour.';
header('Location: ' . BASE_URL . $redirect);
exit;
