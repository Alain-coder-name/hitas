<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/stats/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$direction = $_POST['direction'] ?? '';

$items = $pdo->query('SELECT id, sort_order FROM stats ORDER BY sort_order ASC, id ASC')->fetchAll();
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
        $stmt = $pdo->prepare('UPDATE stats SET sort_order = ? WHERE id = ?');
        $stmt->execute([$b['sort_order'], $a['id']]);
        $stmt->execute([$a['sort_order'], $b['id']]);
    }
}

header('Location: ' . BASE_URL . '/admin/stats/list.php');
exit;
