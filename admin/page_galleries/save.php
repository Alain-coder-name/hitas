<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/page_galleries/hub.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$key = trim($_POST['key'] ?? '');
$validKeys = ['a-propos', 'mission', 'admission-conditions'];
if (!in_array($key, $validKeys, true)) { die('Galerie inconnue.'); }

// Diagnostic clair si l'envoi échoue (photo trop lourde, etc.) au lieu
// d'un échec silencieux.
if (empty($_FILES['image']['name'])) {
    $_SESSION['flash_error'] = 'Merci de choisir une photo.';
    header('Location: ' . BASE_URL . '/admin/page_galleries/form.php?key=' . urlencode($key));
    exit;
}
if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Cette photo est trop volumineuse pour la limite autorisée par le serveur (upload_max_filesize dans php.ini). Essayez une photo plus légère.',
        UPLOAD_ERR_FORM_SIZE  => 'Cette photo dépasse la taille maximale autorisée par le formulaire.',
        UPLOAD_ERR_PARTIAL    => 'La photo n\'a été que partiellement envoyée. Réessayez.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : aucun dossier temporaire disponible pour l\'envoi.',
        UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier sur le disque.',
    ];
    $_SESSION['flash_error'] = $uploadErrors[$_FILES['image']['error']] ?? 'L\'envoi de la photo a échoué (erreur inconnue).';
    header('Location: ' . BASE_URL . '/admin/page_galleries/form.php?key=' . urlencode($key));
    exit;
}

$newImage = handleImageUpload('image', 'page_galleries');
if (!$newImage) {
    $_SESSION['flash_error'] = 'L\'envoi de la photo a échoué.';
    header('Location: ' . BASE_URL . '/admin/page_galleries/form.php?key=' . urlencode($key));
    exit;
}

$stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_galleries WHERE page_key = ?');
$stmt->execute([$key]);
$nextOrder = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('INSERT INTO page_galleries (page_key, image, sort_order) VALUES (?,?,?)');
$stmt->execute([$key, $newImage, $nextOrder]);

$_SESSION['flash_success'] = 'Photo ajoutée avec succès.';
header('Location: ' . BASE_URL . '/admin/page_galleries/list.php?key=' . urlencode($key));
exit;
