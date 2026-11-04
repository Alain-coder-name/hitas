<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/testimonials/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$fullName    = trim($_POST['full_name'] ?? '');
$destination = trim($_POST['destination_country'] ?? '');
$program     = trim($_POST['program'] ?? '');
$content     = trim($_POST['content'] ?? '');
$isPublished = (int)($_POST['is_published'] ?? 1);

if ($fullName === '' || $content === '') {
    $_SESSION['flash_error'] = 'Le nom et l’avis sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/testimonials/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

$newPhoto = handleImageUpload('photo', 'testimonials');
$newVideo = handleVideoUpload('video', 'testimonials');

if ($id) {
    if (!$newPhoto || !$newVideo) {
        $stmt = $pdo->prepare('SELECT photo, video FROM testimonials WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        $newPhoto = $newPhoto ?: ($existing['photo'] ?? null);
        $newVideo = $newVideo ?: ($existing['video'] ?? null);
    }
    $stmt = $pdo->prepare('UPDATE testimonials SET full_name=?, destination_country=?, program=?, content=?, photo=?, video=?, is_published=? WHERE id=?');
    $stmt->execute([$fullName, $destination, $program, $content, $newPhoto, $newVideo, $isPublished, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO testimonials (full_name, destination_country, program, content, photo, video, is_published) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$fullName, $destination, $program, $content, $newPhoto, $newVideo, $isPublished]);
}

$_SESSION['flash_success'] = 'Avis client enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/testimonials/list.php');
exit;
