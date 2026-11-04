<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/faq/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$question    = trim($_POST['question'] ?? '');
$answer      = trim($_POST['answer'] ?? '');
$orderIndex  = (int)($_POST['order_index'] ?? 0);
$isPublished = (int)($_POST['is_published'] ?? 1);

if ($question === '' || $answer === '') {
    $_SESSION['flash_error'] = 'La question et la réponse sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/faq/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE faq SET question=?, answer=?, order_index=?, is_published=? WHERE id=?');
    $stmt->execute([$question, $answer, $orderIndex, $isPublished, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO faq (question, answer, order_index, is_published) VALUES (?,?,?,?)');
    $stmt->execute([$question, $answer, $orderIndex, $isPublished]);
}

$_SESSION['flash_success'] = 'Question enregistrée avec succès.';
header('Location: ' . BASE_URL . '/admin/faq/list.php');
exit;
