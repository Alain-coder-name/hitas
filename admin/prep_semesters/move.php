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
$direction = $_POST['direction'] ?? '';

$stmt = $pdo->prepare('SELECT id, sort_order FROM prep_semesters WHERE prep_block_id = ? ORDER BY sort_order ASC, semester_number ASC');
$stmt->execute([$blockId]);
$items = $stmt->fetchAll();

$position = null;
foreach ($items as $index => $item) {
    if ((int)$item['id'] === $id) {
        $position = $index;
        break;
    }
}

if ($position !== null) {
    $swapWith = $direction === 'up' ? $position - 1 : $position + 1;
    if (isset($items[$swapWith])) {
        $a = $items[$position];
        $b = $items[$swapWith];
        $stmt = $pdo->prepare('UPDATE prep_semesters SET sort_order = ? WHERE id = ?');
        $stmt->execute([$b['sort_order'], $a['id']]);
        $stmt->execute([$a['sort_order'], $b['id']]);
    }
}

header('Location: ' . BASE_URL . '/admin/prep_semesters/list.php?block_id=' . $blockId);
exit;
