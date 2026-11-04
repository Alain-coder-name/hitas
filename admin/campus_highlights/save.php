<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/campus_highlights/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id                = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$name              = trim($_POST['name'] ?? '');
$location          = trim($_POST['location'] ?? '') ?: null;
$title             = trim($_POST['title'] ?? '') ?: null;
$shortDescription  = trim($_POST['short_description'] ?? '');
$fullDescription   = trim($_POST['full_description'] ?? '') ?: null;
$isPublished       = !empty($_POST['is_published']) ? 1 : 0;
$sortOrder         = (int)($_POST['sort_order'] ?? 0);
$newPhoto          = handleImageUpload('photo', 'campus_highlights');

if ($id && !$newPhoto) {
    $stmt = $pdo->prepare('SELECT photo FROM campus_highlights WHERE id = ?');
    $stmt->execute([$id]);
    $newPhoto = $stmt->fetchColumn() ?: null;
}

if ($name === '' || $shortDescription === '') {
    $_SESSION['flash_error'] = 'Le nom et la mini description sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/campus_highlights/' . ($id ? 'form.php?id=' . $id : 'form.php'));
    exit;
}

if (!$id && !$newPhoto) {
    $_SESSION['flash_error'] = 'Veuillez sélectionner une photo.';
    header('Location: ' . BASE_URL . '/admin/campus_highlights/form.php');
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE campus_highlights SET name=?, location=?, title=?, short_description=?, full_description=?, photo=?, is_published=?, sort_order=? WHERE id=?');
    $stmt->execute([$name, $location, $title, $shortDescription, $fullDescription, $newPhoto, $isPublished, $sortOrder, $id]);
    $_SESSION['flash_success'] = 'Fiche mise à jour avec succès.';
} else {
    $stmt = $pdo->prepare('INSERT INTO campus_highlights (name, location, title, short_description, full_description, photo, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$name, $location, $title, $shortDescription, $fullDescription, $newPhoto, $isPublished, $sortOrder]);
    $_SESSION['flash_success'] = 'Fiche ajoutée avec succès.';
}

header('Location: ' . BASE_URL . '/admin/campus_highlights/list.php');
exit;
