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

$id = (int)($payload['id'] ?? 0);
$isActive = !empty($payload['is_active']) ? 1 : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_id']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE home_sections SET is_active = ? WHERE id = ?');
$stmt->execute([$isActive, $id]);

echo json_encode(['ok' => true]);
