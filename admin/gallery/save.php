<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/gallery/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id          = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title       = trim($_POST['title'] ?? '');
$category    = trim($_POST['category'] ?? '');
$sortOrder   = (int)($_POST['sort_order'] ?? 0);
$programId   = isset($_POST['program_id']) && $_POST['program_id'] !== '' ? (int)$_POST['program_id'] : null;
$partnerId   = isset($_POST['partner_id']) && $_POST['partner_id'] !== '' ? (int)$_POST['partner_id'] : null;
$description = trim($_POST['description'] ?? '');
$newImage    = handleImageUpload('image', 'gallery');
$newVideo    = handleVideoUpload('video', 'gallery');
$removeVideo = !empty($_POST['remove_video']);

if ($id) {
    $stmt = $pdo->prepare('SELECT image, video FROM gallery WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch() ?: ['image' => '', 'video' => null];
    if (!$newImage) {
        $newImage = $current['image'] ?: null;
    }
    if (!$newVideo && !$removeVideo) {
        $newVideo = $current['video'] ?: null;
    }
}

// Une carte de galerie a toujours au moins un média : photo et/ou vidéo.
// (La colonne "image" reste NOT NULL : chaîne vide pour une vidéo seule.)
if (!$newImage && !$newVideo) {
    $_SESSION['flash_error'] = 'Veuillez sélectionner une photo ou une vidéo.';
    header('Location: ' . BASE_URL . '/admin/gallery/form.php' . ($id ? "?id=$id" : ''));
    exit;
}
$newImage = $newImage ?: '';

if ($programId !== null) {
    $exists = $pdo->prepare('SELECT 1 FROM programs WHERE id = ? LIMIT 1');
    $exists->execute([$programId]);
    if (!$exists->fetch()) {
        $programId = null;
    }
}

if ($partnerId !== null) {
    $exists = $pdo->prepare('SELECT 1 FROM partners WHERE id = ? LIMIT 1');
    $exists->execute([$partnerId]);
    if (!$exists->fetch()) {
        $partnerId = null;
    }
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE gallery SET title=?, category=?, sort_order=?, description=?, program_id=?, partner_id=?, image=?, video=? WHERE id=?');
    $stmt->execute([$title, $category, $sortOrder, $description, $programId, $partnerId, $newImage, $newVideo, $id]);
    $_SESSION['flash_success'] = 'Élément de la galerie mis à jour avec succès.';
} else {
    $stmt = $pdo->prepare('INSERT INTO gallery (title, category, sort_order, description, program_id, partner_id, image, video) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$title, $category, $sortOrder, $description, $programId, $partnerId, $newImage, $newVideo]);
    $_SESSION['flash_success'] = 'Élément ajouté à la galerie avec succès.';
}

header('Location: ' . BASE_URL . '/admin/gallery/list.php');
exit;
