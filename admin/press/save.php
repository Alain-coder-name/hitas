<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/press/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$title         = trim($_POST['title'] ?? '');
$source        = trim($_POST['source'] ?? '');
$excerpt       = trim($_POST['excerpt'] ?? '');
$externalLink  = trim($_POST['external_link'] ?? '');
$publishedDate = trim($_POST['published_date'] ?? '') ?: null;
$isPublished   = (int)($_POST['is_published'] ?? 1);

if ($title === '') {
    $_SESSION['flash_error'] = 'Le titre est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/press/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

// Diagnostic clair si l'envoi de la photo échoue (photo trop lourde, etc.)
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Cette photo est trop volumineuse pour la limite autorisée par le serveur.',
        UPLOAD_ERR_FORM_SIZE  => 'Cette photo dépasse la taille maximale autorisée par le formulaire.',
        UPLOAD_ERR_PARTIAL    => 'La photo n\'a été que partiellement envoyée. Réessayez.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : aucun dossier temporaire disponible.',
        UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier sur le disque.',
    ];
    $_SESSION['flash_error'] = $uploadErrors[$_FILES['image']['error']] ?? 'L\'envoi de la photo a échoué.';
}

$newImage = handleImageUpload('image', 'press');

if ($id) {
    if (!$newImage) {
        $stmt = $pdo->prepare('SELECT image FROM press_mentions WHERE id = ?');
        $stmt->execute([$id]);
        $newImage = $stmt->fetchColumn() ?: null;
    }
    $stmt = $pdo->prepare('UPDATE press_mentions SET title=?, source=?, excerpt=?, image=?, external_link=?, published_date=?, is_published=? WHERE id=?');
    $stmt->execute([$title, $source, $excerpt, $newImage, $externalLink, $publishedDate, $isPublished, $id]);
} else {
    $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM press_mentions')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO press_mentions (title, source, excerpt, image, external_link, published_date, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$title, $source, $excerpt, $newImage, $externalLink, $publishedDate, $isPublished, $nextOrder]);
}

if (empty($_SESSION['flash_error'])) {
    $_SESSION['flash_success'] = 'Mention presse enregistrée avec succès.';
}
header('Location: ' . BASE_URL . '/admin/press/list.php');
exit;
