<?php
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
$info  = !empty($_GET['expired']) ? 'Votre session a expiré ou a été fermée par sécurité. Merci de vous reconnecter.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'La page a expiré. Rechargez-la puis réessayez.';
    } elseif (loginIsLocked($username)) {
        $error = 'Trop de tentatives échouées. Pour votre sécurité, réessayez dans 15 minutes.';
    } elseif (attemptLogin($username, $password)) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    } else {
        // Message volontairement identique que l'identifiant existe ou non.
        $error = 'Identifiants incorrects.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin - HITAS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-page d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm" style="width: 380px;">
        <div class="card-body p-4">
            <h4 class="text-center mb-1">HITAS</h4>
            <p class="text-center text-muted mb-4">Espace administration</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php elseif ($info): ?>
                <div class="alert alert-info py-2"><?= e($info) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="mb-3">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-control" required autofocus maxlength="100" autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" required maxlength="1000" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>
