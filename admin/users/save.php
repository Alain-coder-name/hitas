<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/users/list.php');
    exit;
}
checkCsrf();

$pdo = getDB();
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'redacteur';
if (!array_key_exists($role, adminRoleLabels())) {
    $role = 'redacteur';
}
$isActive = (int)($_POST['is_active'] ?? 1);

if ($username === '' || $email === '') {
    $_SESSION['flash_error'] = 'Le nom d\'utilisateur et l\'email sont obligatoires.';
    header('Location: ' . BASE_URL . '/admin/users/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

if (!$id && $password === '') {
    $_SESSION['flash_error'] = 'Le mot de passe est obligatoire pour un nouvel utilisateur.';
    header('Location: ' . BASE_URL . '/admin/users/form.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = 'L\'adresse email n\'est pas valide.';
    header('Location: ' . BASE_URL . '/admin/users/form.php' . ($id ? "?id=$id" : ''));
    exit;
}

// Politique de mot de passe : un compte admin protège tout le site.
if ($password !== '') {
    $weak = mb_strlen($password) < 10
        || !preg_match('/[A-Za-z]/', $password)
        || !preg_match('/\d/', $password)
        || mb_strtolower($password) === mb_strtolower($username);
    if ($weak) {
        $_SESSION['flash_error'] = 'Mot de passe trop faible : 10 caractères minimum, avec des lettres ET des chiffres (idéalement aussi une majuscule et un symbole), et différent du nom d\'utilisateur.';
        header('Location: ' . BASE_URL . '/admin/users/form.php' . ($id ? "?id=$id" : ''));
        exit;
    }
}

if ($id) {
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE admin_users SET username=?, email=?, password_hash=?, role=?, is_active=? WHERE id=?');
        $stmt->execute([$username, $email, $hash, $role, $isActive, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE admin_users SET username=?, email=?, role=?, is_active=? WHERE id=?');
        $stmt->execute([$username, $email, $role, $isActive, $id]);
    }
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, role, is_active) VALUES (?,?,?,?,?)');
    $stmt->execute([$username, $email, $hash, $role, $isActive]);
}

$_SESSION['flash_success'] = 'Utilisateur enregistré avec succès.';
header('Location: ' . BASE_URL . '/admin/users/list.php');
exit;
