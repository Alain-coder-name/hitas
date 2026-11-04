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
$blockId = (int)($_POST['block_id'] ?? 0);

$pdo->prepare('DELETE FROM prep_semesters WHERE id = ? AND prep_block_id = ?')->execute([$id, $blockId]);

$_SESSION['flash_success'] = 'Semestre supprimé.';
header('Location: ' . BASE_URL . '/admin/prep_semesters/list.php?block_id=' . $blockId);
exit;
