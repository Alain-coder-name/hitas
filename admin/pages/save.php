<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/pages/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id      = (int)($_POST['id'] ?? 0);
$title   = trim($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';

if ($title === '') {
    $_SESSION['flash_error'] = 'Le titre est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/pages/edit.php?id=' . $id);
    exit;
}

// Diagnostic clair si une photo a été choisie mais que l'envoi a échoué
// (au lieu d'échouer silencieusement, comme avant) — la cause la plus
// fréquente est une photo trop lourde pour la limite du serveur.
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Cette photo est trop volumineuse pour la limite autorisée par le serveur (upload_max_filesize dans php.ini). Essayez une photo plus légère, ou augmentez cette limite.',
        UPLOAD_ERR_FORM_SIZE  => 'Cette photo dépasse la taille maximale autorisée par le formulaire.',
        UPLOAD_ERR_PARTIAL    => 'La photo n\'a été que partiellement envoyée. Réessayez.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : aucun dossier temporaire disponible pour l\'envoi.',
        UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier sur le disque.',
    ];
    $_SESSION['flash_error'] = $uploadErrors[$_FILES['image']['error']] ?? 'L\'envoi de la photo a échoué (erreur inconnue). Le reste de la page a tout de même été enregistré.';
}

$newImage = handleImageUpload('image', 'pages');
if ($newImage) {
    $stmt = $pdo->prepare('UPDATE pages SET title=?, content=?, image=? WHERE id=?');
    $stmt->execute([$title, $content, $newImage, $id]);
} else {
    $stmt = $pdo->prepare('UPDATE pages SET title=?, content=? WHERE id=?');
    $stmt->execute([$title, $content, $id]);
}

$_SESSION['flash_success'] = empty($_SESSION['flash_error']) ? 'Page mise à jour avec succès.' : 'Le titre et le contenu ont été enregistrés — voir le message ci-dessus pour la photo.';
header('Location: ' . BASE_URL . '/admin/pages/list.php');
exit;
