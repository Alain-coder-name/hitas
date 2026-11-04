<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/team/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$fullName        = trim($_POST['full_name'] ?? '');
$role            = trim($_POST['role'] ?? '');
$tier            = in_array($_POST['tier'] ?? '', ['gold', 'silver', 'bronze'], true) ? $_POST['tier'] : 'bronze';
$contactEmail    = trim($_POST['contact_email'] ?? '');
$contactWhatsapp = trim($_POST['contact_whatsapp'] ?? '');
$bio             = trim($_POST['bio'] ?? '');
$verifyLink      = trim($_POST['verify_link'] ?? '');
$orderIndex      = (int)($_POST['order_index'] ?? 0);
$isPublished     = (int)($_POST['is_published'] ?? 1);

if ($fullName === '') {
    $_SESSION['flash_error'] = 'Le nom est obligatoire.';
    header('Location: ' . BASE_URL . '/admin/team/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

$newPhoto = handleImageUpload('photo', 'team');

if ($id) {
    if (!$newPhoto) {
        $stmt = $pdo->prepare('SELECT photo FROM team_members WHERE id = ?');
        $stmt->execute([$id]);
        $newPhoto = $stmt->fetchColumn() ?: null;
    }
    $stmt = $pdo->prepare('UPDATE team_members SET full_name=?, role=?, tier=?, contact_email=?, contact_whatsapp=?, bio=?, verify_link=?, photo=?, order_index=?, is_published=? WHERE id=?');
    $stmt->execute([$fullName, $role, $tier, $contactEmail, $contactWhatsapp, $bio, $verifyLink, $newPhoto, $orderIndex, $isPublished, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO team_members (full_name, role, tier, contact_email, contact_whatsapp, bio, verify_link, photo, order_index, is_published) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$fullName, $role, $tier, $contactEmail, $contactWhatsapp, $bio, $verifyLink, $newPhoto, $orderIndex, $isPublished]);
}

$_SESSION['flash_success'] = 'Membre enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/team/list.php');
exit;
