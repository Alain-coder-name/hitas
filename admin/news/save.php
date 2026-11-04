<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/news/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$title       = trim($_POST['title'] ?? '');
$excerpt         = trim($_POST['excerpt'] ?? '');
$content         = trim($_POST['content'] ?? '');
$isPublished     = (int)($_POST['is_published'] ?? 1);
$isAdvertisement = (int)($_POST['is_advertisement'] ?? 0);
$externalLink    = trim($_POST['external_link'] ?? '');

if ($title === '') {
    $_SESSION['flash_error'] = 'Le titre est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/news/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if ($isAdvertisement && $externalLink === '') {
    $_SESSION['flash_error'] = 'Une publicité doit avoir un lien externe de destination.';
    header('Location: ' . BASE_URL . '/admin/news/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if ($externalLink !== '' && !filter_var($externalLink, FILTER_VALIDATE_URL)) {
    $_SESSION['flash_error'] = 'Le lien externe doit être une URL valide (ex: https://exemple.com).';
    header('Location: ' . BASE_URL . '/admin/news/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if (!$isAdvertisement) {
    $externalLink = null; // on ne garde le lien externe que pour les publicités
}

$newVideo = handleVideoUpload('video', 'news');

if ($id) {
    if (!$newVideo) {
        $stmt = $pdo->prepare('SELECT video FROM news WHERE id = ?');
        $stmt->execute([$id]);
        $newVideo = $stmt->fetchColumn() ?: null;
    }
    // Le slug n'est PAS régénéré ici : il peut avoir été personnalisé
    // depuis le module SEO (admin/seo). On ne le touche qu'à la création.
    // La colonne "image" n'est pas écrite ici : elle est synchronisée plus
    // bas, une fois la galerie de photos (news_images) traitée.
    $stmt = $pdo->prepare('UPDATE news SET title=?, excerpt=?, content=?, video=?, is_published=?, is_advertisement=?, external_link=? WHERE id=?');
    $stmt->execute([$title, $excerpt, $content, $newVideo, $isPublished, $isAdvertisement, $externalLink, $id]);
} else {
    $slug = uniqueSlug($pdo, 'news', slugify($title));
    $stmt = $pdo->prepare('INSERT INTO news (title, slug, excerpt, content, image, video, is_published, is_advertisement, external_link, published_at) VALUES (?,?,?,?,NULL,?,?,?,?,NOW())');
    $stmt->execute([$title, $slug, $excerpt, $content, $newVideo, $isPublished, $isAdvertisement, $externalLink]);
    $id = (int)$pdo->lastInsertId();
}

/* =========================================================================
   Galerie de photos (news_images) : plusieurs photos par actualité, dont
   une seule "de couverture" (celle utilisée à l'accueil et dans les
   listes). La colonne news.image reste synchronisée automatiquement sur
   cette photo de couverture, pour que tout le reste du site (déjà câblé
   sur cette colonne) continue de fonctionner sans changement.
   ========================================================================= */
$deleteIds = array_map('intval', $_POST['delete_ids'] ?? []);
if ($deleteIds) {
    $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
    $pdo->prepare("DELETE FROM news_images WHERE news_id = ? AND id IN ($placeholders)")
        ->execute(array_merge([$id], array_values($deleteIds)));
}

if (!empty($_FILES['new_images']['name'][0])) {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM news_images WHERE news_id = ?');
    $stmt->execute([$id]);
    $nextOrder = (int)$stmt->fetchColumn() + 1;

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $destDir = UPLOAD_PATH . 'news/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $insertImg = $pdo->prepare('INSERT INTO news_images (news_id, image, is_cover, sort_order) VALUES (?, ?, 0, ?)');
    foreach ($_FILES['new_images']['name'] as $i => $name) {
        if ($name === '' || $_FILES['new_images']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            continue;
        }
        $tmpFile = $_FILES['new_images']['tmp_name'][$i];
        if (!is_uploaded_file($tmpFile) || !uploadContentIsValid($tmpFile, $ext, 'image')) {
            hitasSecurityLog('actualité : photo refusée (contenu invalide) ' . oneLine($name, 60));
            continue;
        }
        $filename = 'news_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $destDir . $filename)) {
            $insertImg->execute([$id, $filename, $nextOrder]);
            $nextOrder++;
        }
    }
}

$requestedCoverId = !empty($_POST['cover_id']) ? (int)$_POST['cover_id'] : null;
if ($requestedCoverId) {
    // Un seul "vrai" (1) : les autres repassent à 0 en une seule requête.
    $pdo->prepare('UPDATE news_images SET is_cover = (id = ?) WHERE news_id = ?')
        ->execute([$requestedCoverId, $id]);
}

// Filet de sécurité : si aucune photo n'est marquée "couverture" (ex :
// première photo tout juste ajoutée, ou couverture supprimée), la plus
// ancienne photo restante est promue automatiquement.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM news_images WHERE news_id = ? AND is_cover = 1');
$stmt->execute([$id]);
if ((int)$stmt->fetchColumn() === 0) {
    $stmt = $pdo->prepare('SELECT id FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmt->execute([$id]);
    $firstId = $stmt->fetchColumn();
    if ($firstId) {
        $pdo->prepare('UPDATE news_images SET is_cover = 1 WHERE id = ?')->execute([$firstId]);
    }
}

$stmt = $pdo->prepare('SELECT image FROM news_images WHERE news_id = ? AND is_cover = 1 LIMIT 1');
$stmt->execute([$id]);
$coverImage = $stmt->fetchColumn() ?: null;
$pdo->prepare('UPDATE news SET image = ? WHERE id = ?')->execute([$coverImage, $id]);

$_SESSION['flash_success'] = 'Article enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/news/list.php');
exit;
