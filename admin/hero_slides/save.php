<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/hero_slides/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id          = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$caption     = trim($_POST['caption'] ?? '');
$title       = trim($_POST['title'] ?? '') ?: null;
$subtitle    = trim($_POST['subtitle'] ?? '') ?: null;
$buttonText  = trim($_POST['button_text'] ?? '') ?: null;
$buttonLink  = trim($_POST['button_link'] ?? '') ?: null;
$durationMs  = (int)round((float)($_POST['duration_seconds'] ?? 6) * 1000);
if ($durationMs < 1000) { $durationMs = 6000; }
$isPublished = !empty($_POST['is_published']) ? 1 : 0;

$newImage = handleImageUpload('image', 'hero');
$newVideo = handleVideoUpload('video', 'hero');

$existingImage = null;
$existingVideo = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT image, video FROM hero_slides WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $existingImage = $row['image'] ?? null;
    $existingVideo = $row['video'] ?? null;
}

$image = $newImage ?: $existingImage;
$video = $newVideo ?: $existingVideo;

if (!$image && !$video) {
    $_SESSION['flash_error'] = 'Veuillez fournir au moins une image ou une vidéo.';
    header('Location: ' . BASE_URL . '/admin/hero_slides/' . ($id ? 'form.php?id=' . $id : 'form.php'));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE hero_slides SET caption=?, title=?, subtitle=?, button_text=?, button_link=?, duration_ms=?, is_published=?, image=?, video=? WHERE id=?');
    $stmt->execute([$caption, $title, $subtitle, $buttonText, $buttonLink, $durationMs, $isPublished, $image, $video, $id]);
    $_SESSION['flash_success'] = 'Slide mise à jour avec succès.';
} else {
    $stmt = $pdo->prepare('INSERT INTO hero_slides (caption, title, subtitle, button_text, button_link, duration_ms, is_published, image, video) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$caption, $title, $subtitle, $buttonText, $buttonLink, $durationMs, $isPublished, $image, $video]);
    $_SESSION['flash_success'] = 'Slide ajoutée avec succès.';
}

header('Location: ' . BASE_URL . '/admin/hero_slides/list.php');
exit;
