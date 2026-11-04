<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/prep_blocks/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$title          = trim($_POST['title'] ?? '');
$description    = trim($_POST['description'] ?? '');
$isPublished    = (int)($_POST['is_published'] ?? 1);
$programIds     = array_map('intval', $_POST['program_ids'] ?? []);

if ($title === '') {
    $_SESSION['flash_error'] = 'Le titre est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/prep_blocks/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE prep_blocks SET title=?, description=?, is_published=? WHERE id=?');
    $stmt->execute([$title, $description, $isPublished, $id]);
} else {
    $nextOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM prep_blocks')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO prep_blocks (title, description, sort_order, is_published) VALUES (?,?,?,?)');
    $stmt->execute([$title, $description, $nextOrder, $isPublished]);
    $id = (int)$pdo->lastInsertId();
}

// Met à jour les liaisons avec les filières
$pdo->prepare('DELETE FROM prep_block_programs WHERE prep_block_id = ?')->execute([$id]);
if ($programIds) {
    $stmt = $pdo->prepare('INSERT INTO prep_block_programs (prep_block_id, program_id) VALUES (?, ?)');
    foreach ($programIds as $pid) {
        $stmt->execute([$id, $pid]);
    }
}

$_SESSION['flash_success'] = 'Bloc de mise à niveau enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/prep_blocks/list.php');
exit;
