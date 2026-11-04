<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/programs/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$title             = trim($_POST['title'] ?? '');
$shortDescription  = trim($_POST['short_description'] ?? '');
$description       = trim($_POST['description'] ?? '');
$duration          = trim($_POST['duration'] ?? '');
$degreeLevel       = trim($_POST['degree_level'] ?? '');
$category          = trim($_POST['category'] ?? '');
$prerequisites     = trim($_POST['prerequisites'] ?? '');
$outcomes          = trim($_POST['outcomes'] ?? '');
$isPublished       = (int)($_POST['is_published'] ?? 1);
$isFeatured        = !empty($_POST['is_featured']) ? 1 : 0;
$featuredOrder     = (int)($_POST['featured_order'] ?? 0);
$partnerIds        = array_map('intval', $_POST['partner_ids'] ?? []);
$partnerUrls       = $_POST['partner_url'] ?? []; // [partner_id => url], seulement pour les partenaires cochés

if ($title === '') {
    $_SESSION['flash_error'] = 'Le titre est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/programs/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

$newImage = handleImageUpload('image', 'programs');

if ($id) {
    // Récupère l'ancienne image si aucune nouvelle n'est envoyée
    if (!$newImage) {
        $stmt = $pdo->prepare('SELECT image FROM programs WHERE id = ?');
        $stmt->execute([$id]);
        $newImage = $stmt->fetchColumn() ?: null;
    }

    // Le slug n'est PAS régénéré ici : il peut avoir été personnalisé
    // depuis le module SEO (admin/seo). On ne le touche qu'à la création.
    $stmt = $pdo->prepare('UPDATE programs SET title=?, short_description=?, description=?, duration=?, degree_level=?, category=?, prerequisites=?, outcomes=?, image=?, is_published=?, is_featured=?, featured_order=? WHERE id=?');
    $stmt->execute([$title, $shortDescription, $description, $duration, $degreeLevel, $category, $prerequisites, $outcomes, $newImage, $isPublished, $isFeatured, $featuredOrder, $id]);
} else {
    $slug = uniqueSlug($pdo, 'programs', slugify($title));
    $stmt = $pdo->prepare('INSERT INTO programs (title, slug, short_description, description, duration, degree_level, category, prerequisites, outcomes, image, is_published, is_featured, featured_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$title, $slug, $shortDescription, $description, $duration, $degreeLevel, $category, $prerequisites, $outcomes, $newImage, $isPublished, $isFeatured, $featuredOrder]);
    $id = (int)$pdo->lastInsertId();
}

// Met à jour les liaisons avec les partenaires — seulement les ajouts/retraits
// réels : un DELETE+INSERT complet effacerait le lien précis (partner_url)
// déjà renseigné pour les partenaires qui restent cochés.
$stmt = $pdo->prepare('SELECT partner_id FROM program_partners WHERE program_id = ?');
$stmt->execute([$id]);
$existingPartnerIds = array_map('intval', array_column($stmt->fetchAll(), 'partner_id'));

$toRemove = array_diff($existingPartnerIds, $partnerIds);
$toAdd = array_diff($partnerIds, $existingPartnerIds);

if ($toRemove) {
    $placeholders = implode(',', array_fill(0, count($toRemove), '?'));
    $pdo->prepare("DELETE FROM program_partners WHERE program_id = ? AND partner_id IN ($placeholders)")
        ->execute(array_merge([$id], array_values($toRemove)));
}
if ($toAdd) {
    $stmt = $pdo->prepare('INSERT INTO program_partners (program_id, partner_id) VALUES (?, ?)');
    foreach ($toAdd as $pid) {
        $stmt->execute([$id, $pid]);
    }
}

// Lien précis par partenaire : mis à jour pour tous les partenaires cochés
// (nouveaux ET déjà liés), séparément du INSERT ci-dessus qui ne gère que
// les ajouts/retraits de la liaison elle-même.
if ($partnerIds) {
    $stmt = $pdo->prepare('UPDATE program_partners SET partner_url = ? WHERE program_id = ? AND partner_id = ?');
    foreach ($partnerIds as $pid) {
        $url = trim((string)($partnerUrls[$pid] ?? ''));
        $stmt->execute([$url !== '' ? $url : null, $id, $pid]);
    }
}

$_SESSION['flash_success'] = 'Filière enregistrée avec succès.';
header('Location: ' . BASE_URL . '/admin/programs/list.php');
exit;
