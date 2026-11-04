<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

// Trois états possibles, pour un suivi simple et lisible : le message n'a pas
// encore été géré, il est en cours de traitement, ou il a été géré.
// Les anciennes valeurs ("nouveau", "lu") sont automatiquement comprises
// comme "non_traite" tant qu'elles n'ont pas été mises à jour.
function messageStatusKey(array $m): string
{
    $status = $m['status'] ?? '';
    if (in_array($status, ['en_cours', 'traite'], true)) {
        return $status;
    }
    return 'non_traite';
}

$statusLabels = [
    'non_traite' => ['Pas encore géré', 'bg-danger'],
    'en_cours'   => ['En cours', 'bg-warning text-dark'],
    'traite'     => ['Géré', 'bg-success'],
];

$counts = ['non_traite' => 0, 'en_cours' => 0, 'traite' => 0];
foreach ($messages as $m) {
    $counts[messageStatusKey($m)]++;
}

$pageTitle = 'Messages reçus';
include __DIR__ . '/../header.php';
?>

<h5 class="mb-1">Suivi des messages (contact / pré-inscription)</h5>
<p class="text-muted small mb-3">Vue d'ensemble : où en est chaque message reçu depuis le site. Changez le statut au fur et à mesure de leur traitement, sans avoir à modifier le contenu.</p>

<div class="msg-status-summary">
    <div class="msg-status-card msg-status-card--non_traite">
        <div>
            <div class="msg-status-count"><?= (int)$counts['non_traite'] ?></div>
            <div class="msg-status-label">Pas encore géré</div>
        </div>
    </div>
    <div class="msg-status-card msg-status-card--en_cours">
        <div>
            <div class="msg-status-count"><?= (int)$counts['en_cours'] ?></div>
            <div class="msg-status-label">En cours</div>
        </div>
    </div>
    <div class="msg-status-card msg-status-card--traite">
        <div>
            <div class="msg-status-count"><?= (int)$counts['traite'] ?></div>
            <div class="msg-status-label">Géré</div>
        </div>
    </div>
</div>

<div class="card p-3">
    <table class="table align-middle">
        <thead><tr><th>Statut</th><th>Nom</th><th>Email</th><th>Sujet</th><th>Date</th><th style="width:160px;"></th></tr></thead>
        <tbody>
        <?php foreach ($messages as $m): ?>
            <?php $statusKey = messageStatusKey($m); $label = $statusLabels[$statusKey]; ?>
            <tr>
                <td>
                    <form action="<?= BASE_URL ?>/admin/messages/status.php" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <input type="hidden" name="redirect" value="/admin/messages/list.php">
                        <select name="status" class="form-select form-select-sm badge-select <?= $label[1] ?>" onchange="this.form.submit()" style="width:auto; display:inline-block;">
                            <?php foreach ($statusLabels as $key => $lbl): ?>
                                <option value="<?= e($key) ?>" <?= $statusKey === $key ? 'selected' : '' ?>><?= e($lbl[0]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><?= e($m['full_name']) ?></td>
                <td><?= e($m['email']) ?></td>
                <td><?= e($m['subject']) ?></td>
                <td><?= e($m['created_at']) ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>/admin/messages/view.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    <form action="<?= BASE_URL ?>/admin/messages/delete.php" method="post" class="d-inline js-confirm-delete">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$messages): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucun message pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
