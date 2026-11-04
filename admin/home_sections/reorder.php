<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$token = $payload['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit;
}

$order = $payload['order'] ?? [];
if (!is_array($order) || !$order) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_order']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE home_sections SET sort_order = ? WHERE id = ?');
foreach ($order as $index => $id) {
    $stmt->execute([$index + 1, (int)$id]);
}

echo json_encode(['ok' => true]);
