<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/prep_blocks/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$blockId = (int)($_POST['block_id'] ?? 0);
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$semesterNumber = (int)($_POST['semester_number'] ?? 1);
$periodLabel    = trim($_POST['period_label'] ?? '');
$subjects       = trim($_POST['subjects'] ?? '');

if ($periodLabel === '') {
    $_SESSION['flash_error'] = 'La période est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/prep_semesters/form.php?block_id=' . $blockId . ($id ? '&id=' . $id : ''));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE prep_semesters SET semester_number=?, period_label=?, subjects=? WHERE id=? AND prep_block_id=?');
    $stmt->execute([$semesterNumber, $periodLabel, $subjects, $id, $blockId]);
} else {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM prep_semesters WHERE prep_block_id = ?');
    $stmt->execute([$blockId]);
    $nextOrder = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('INSERT INTO prep_semesters (prep_block_id, semester_number, period_label, subjects, sort_order) VALUES (?,?,?,?,?)');
    $stmt->execute([$blockId, $semesterNumber, $periodLabel, $subjects, $nextOrder]);
}

$_SESSION['flash_success'] = 'Semestre enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/prep_semesters/list.php?block_id=' . $blockId);
exit;
