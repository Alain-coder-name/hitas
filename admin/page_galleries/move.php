<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/page_galleries/hub.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$key = trim($_POST['key'] ?? '');
$direction = $_POST['direction'] ?? '';

$stmt = $pdo->prepare('SELECT id, sort_order FROM page_galleries WHERE page_key = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$key]);
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
        $stmt = $pdo->prepare('UPDATE page_galleries SET sort_order = ? WHERE id = ?');
        $stmt->execute([$b['sort_order'], $a['id']]);
        $stmt->execute([$a['sort_order'], $b['id']]);
    }
}

header('Location: ' . BASE_URL . '/admin/page_galleries/list.php?key=' . urlencode($key));
exit;
