<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user = ['username' => '', 'email' => '', 'role' => 'redacteur', 'is_active' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Utilisateur introuvable.'); }
    $user = $found;
}

$pageTitle = $id ? 'Modifier un utilisateur' : 'Ajouter un utilisateur';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 600px;">
    <form action="<?= BASE_URL ?>/admin/users/save.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nom d'utilisateur</label>
            <input type="text" name="username" class="form-control" value="<?= e($user['username']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label"><?= $id ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe' ?></label>
            <input type="password" name="password" class="form-control" minlength="10" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
            <div class="form-text">10 caractères minimum, avec des lettres et des chiffres (idéalement aussi une majuscule et un symbole).</div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-select">
                    <?php foreach (adminRoleLabels() as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $user['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Statut</label>
                <select name="is_active" class="form-select">
                    <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Actif</option>
                    <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Désactivé</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/users/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
