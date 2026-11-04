<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pdo = getDB();
$users = $pdo->query('SELECT * FROM admin_users ORDER BY username')->fetchAll();
$roleLabels = adminRoleLabels();

$pageTitle = 'Utilisateurs admin';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Utilisateurs de l'administration</h5>
    <a href="<?= BASE_URL ?>/admin/users/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter un utilisateur</a>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Nom d'utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th style="width:140px;"></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['username']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($roleLabels[$u['role']] ?? $u['role']) ?></td>
                <td><?= $u['is_active'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Désactivé</span>' ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/users/form.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
                        <form action="<?= BASE_URL ?>/admin/users/delete.php" method="post" class="d-inline js-confirm-delete">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
