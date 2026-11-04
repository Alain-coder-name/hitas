<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/partners/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$name             = trim($_POST['name'] ?? '');
$country          = trim($_POST['country'] ?? '');
$degreeLevel      = trim($_POST['degree_level'] ?? '');
$flagEmoji        = trim($_POST['flag_emoji'] ?? '');
$description      = trim($_POST['description'] ?? '');
$coDiplomationNote = trim($_POST['co_diplomation_note'] ?? '');
$specialties      = trim($_POST['specialties'] ?? '');
$website          = trim($_POST['website'] ?? '');
$isPublished      = (int)($_POST['is_published'] ?? 1);
$programIds       = array_map('intval', $_POST['program_ids'] ?? []);
$cycleUrls        = $_POST['cycle_url'] ?? []; // [categorie => url]

if ($name === '' || $country === '') {
    $_SESSION['flash_error'] = 'Le nom et le pays sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/partners/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

$newLogo = handleImageUpload('logo', 'partners');

if ($id) {
    if (!$newLogo) {
        $stmt = $pdo->prepare('SELECT logo FROM partners WHERE id = ?');
        $stmt->execute([$id]);
        $newLogo = $stmt->fetchColumn() ?: null;
    }
    $stmt = $pdo->prepare('UPDATE partners SET name=?, country=?, degree_level=?, flag_emoji=?, description=?, co_diplomation_note=?, specialties=?, website=?, logo=?, is_published=? WHERE id=?');
    $stmt->execute([$name, $country, $degreeLevel, $flagEmoji, $description, $coDiplomationNote, $specialties, $website, $newLogo, $isPublished, $id]);
} else {
    $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM partners')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO partners (name, country, degree_level, flag_emoji, description, co_diplomation_note, specialties, website, logo, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$name, $country, $degreeLevel, $flagEmoji, $description, $coDiplomationNote, $specialties, $website, $newLogo, $isPublished, $nextOrder]);
    $id = (int)$pdo->lastInsertId();
}

// Met à jour les liaisons avec les filières (mêmes liens que ceux gérés
// depuis la fiche Programme — deux façons d'arriver au même résultat).
// On ne touche qu'aux ajouts/retraits réels : un DELETE+INSERT complet
// effacerait le lien précis (partner_url) déjà renseigné pour les filières
// qui restent cochées.
$stmt = $pdo->prepare('SELECT program_id FROM program_partners WHERE partner_id = ?');
$stmt->execute([$id]);
$existingProgramIds = array_map('intval', array_column($stmt->fetchAll(), 'program_id'));

$toRemove = array_diff($existingProgramIds, $programIds);
$toAdd = array_diff($programIds, $existingProgramIds);

if ($toRemove) {
    $placeholders = implode(',', array_fill(0, count($toRemove), '?'));
    $pdo->prepare("DELETE FROM program_partners WHERE partner_id = ? AND program_id IN ($placeholders)")
        ->execute(array_merge([$id], array_values($toRemove)));
}
if ($toAdd) {
    $stmt = $pdo->prepare('INSERT INTO program_partners (partner_id, program_id) VALUES (?, ?)');
    foreach ($toAdd as $pid) {
        $stmt->execute([$id, $pid]);
    }
}

// Liens précis par cycle (une ligne par catégorie proposée par ce
// partenaire) : mise à jour/insertion si une URL est fournie, suppression
// si le champ est vidé.
foreach ($cycleUrls as $category => $url) {
    $category = trim((string)$category);
    $url = trim((string)$url);
    if ($category === '') {
        continue;
    }
    if ($url === '') {
        $pdo->prepare('DELETE FROM partner_cycle_links WHERE partner_id = ? AND category = ?')
            ->execute([$id, $category]);
    } else {
        $pdo->prepare('INSERT INTO partner_cycle_links (partner_id, category, url) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE url = VALUES(url)')
            ->execute([$id, $category, $url]);
    }
}

$_SESSION['flash_success'] = 'Partenaire enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/partners/list.php');
exit;
