<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/navigation/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = (int)($_POST['id'] ?? 0);
$direction = $_POST['direction'] ?? '';

$items = $pdo->query('SELECT id, order_index FROM nav_menu ORDER BY order_index, id')->fetchAll();
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
        $stmt = $pdo->prepare('UPDATE nav_menu SET order_index = ? WHERE id = ?');
        $stmt->execute([$b['order_index'], $a['id']]);
        $stmt->execute([$a['order_index'], $b['id']]);
    }
}

header('Location: ' . BASE_URL . '/admin/navigation/list.php');
exit;
