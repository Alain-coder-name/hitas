<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);
$message = $stmt->fetch();
if (!$message) { die('Message introuvable.'); }

// Marque comme lu, sans changer un statut déjà en cours / géré.
if (!$message['is_read']) {
    $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    $message['is_read'] = 1;
}

$statusLabels = [
    'non_traite' => 'Pas encore géré',
    'en_cours'   => 'En cours',
    'traite'     => 'Géré',
];
$currentStatus = in_array($message['status'] ?? '', ['en_cours', 'traite'], true) ? $message['status'] : 'non_traite';

$pageTitle = 'Message de ' . $message['full_name'];
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <dl class="row mb-0">
        <dt class="col-sm-3">Nom</dt><dd class="col-sm-9"><?= e($message['full_name']) ?></dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e($message['email']) ?></dd>
        <dt class="col-sm-3">Téléphone</dt><dd class="col-sm-9"><?= e($message['phone']) ?: '—' ?></dd>
        <dt class="col-sm-3">Sujet</dt><dd class="col-sm-9"><?= e($message['subject']) ?: '—' ?></dd>
        <dt class="col-sm-3">Date</dt><dd class="col-sm-9"><?= e($message['created_at']) ?></dd>
        <dt class="col-sm-3">Message</dt><dd class="col-sm-9" style="white-space: pre-wrap;"><?= e($message['message']) ?></dd>
        <dt class="col-sm-3">Statut</dt>
        <dd class="col-sm-9">
            <form action="<?= BASE_URL ?>/admin/messages/status.php" method="post" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$message['id'] ?>">
                <select name="status" class="form-select form-select-sm" style="width:auto;">
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $currentStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary">Mettre à jour</button>
            </form>
        </dd>
    </dl>
    <div class="mt-3">
        <a href="mailto:<?= e($message['email']) ?>" class="btn btn-primary btn-sm">Répondre par email</a>
        <a href="<?= BASE_URL ?>/admin/messages/list.php" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
