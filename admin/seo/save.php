<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/seo/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

$tables = [
    'page'    => ['table' => 'pages', 'slugEditable' => false, 'uploadFolder' => 'pages'],
    'program' => ['table' => 'programs', 'slugEditable' => true, 'uploadFolder' => 'programs'],
    'news'    => ['table' => 'news', 'slugEditable' => true, 'uploadFolder' => 'news'],
];

if (!isset($tables[$type])) {
    die('Type invalide.');
}
$conf = $tables[$type];
$table = $conf['table'];

$metaTitle = trim($_POST['meta_title'] ?? '');
$metaDescription = trim($_POST['meta_description'] ?? '');
$newOgImage = handleImageUpload('og_image', $conf['uploadFolder']);

if (!$newOgImage) {
    $stmt = $pdo->prepare("SELECT og_image FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $newOgImage = $stmt->fetchColumn() ?: null;
}

if ($conf['slugEditable']) {
    $slug = trim($_POST['slug'] ?? '');
    $slug = $slug !== '' ? preg_replace('/[^a-z0-9\-]+/', '-', strtolower($slug)) : '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        $_SESSION['flash_error'] = 'Le slug ne peut pas être vide.';
        header('Location: ' . BASE_URL . "/admin/seo/edit.php?type={$type}&id={$id}");
        exit;
    }

    $check = $pdo->prepare("SELECT id FROM {$table} WHERE slug = ? AND id != ?");
    $check->execute([$slug, $id]);
    if ($check->fetch()) {
        $_SESSION['flash_error'] = 'Ce slug est déjà utilisé par un autre élément. Choisissez-en un autre.';
        header('Location: ' . BASE_URL . "/admin/seo/edit.php?type={$type}&id={$id}");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE {$table} SET slug=?, meta_title=?, meta_description=?, og_image=? WHERE id=?");
    $stmt->execute([$slug, $metaTitle ?: null, $metaDescription ?: null, $newOgImage, $id]);
} else {
    $stmt = $pdo->prepare("UPDATE {$table} SET meta_title=?, meta_description=?, og_image=? WHERE id=?");
    $stmt->execute([$metaTitle ?: null, $metaDescription ?: null, $newOgImage, $id]);
}

$_SESSION['flash_success'] = 'SEO mis à jour avec succès.';
header('Location: ' . BASE_URL . '/admin/seo/list.php');
exit;
