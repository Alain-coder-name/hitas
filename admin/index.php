<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$adminRole = $_SESSION['admin_role'] ?? '';
$pageTitle = 'Tableau de bord';
include __DIR__ . '/header.php';

// =========================================================
// RÉDACTEUR — accès unique aux Filières : un simple raccourci, rien de
// plus (pas de vraies statistiques à lui montrer, ce n'est pas son rôle).
// =========================================================
if ($adminRole === 'redacteur'):
    $programCount = (int) $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn();
    ?>
    <div class="card p-4 text-center" style="max-width: 480px; margin: 2rem auto;">
        <i class="bi bi-mortarboard fs-1 text-muted mb-3"></i>
        <h5 class="mb-2">Bienvenue</h5>
        <p class="text-muted mb-3">Vous gérez les filières de HITAS — <?= $programCount ?> filière<?= $programCount > 1 ? 's' : '' ?> actuellement enregistrée<?= $programCount > 1 ? 's' : '' ?>.</p>
        <a href="<?= BASE_URL ?>/admin/programs/list.php" class="btn btn-primary">Ouvrir Filières</a>
    </div>

<?php
// =========================================================
// DIRECTION (boss) — synthèse honnête : ce qui PEUT réellement être
// mesuré avec les données existantes (suivi des messages, volumes de
// contenu, activité récente). Le site ne trace pas "qui a fait quoi"
// (aucun compte n'est attaché à chaque filière/actualité créée), donc
// impossible de produire un vrai rapport nominatif par employé sans
// ajouter ce suivi au système — cette page reste donc une synthèse
// d'activité globale, pas un rapport par personne.
// =========================================================
elseif ($adminRole === 'boss'):
    $msgStatusCounts = ['non_traite' => 0, 'en_cours' => 0, 'traite' => 0];
    $stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM contact_messages GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        $key = in_array($row['status'], ['en_cours', 'traite'], true) ? $row['status'] : 'non_traite';
        $msgStatusCounts[$key] += (int) $row['cnt'];
    }
    $totalMessages = array_sum($msgStatusCounts);
    $treatedRate = $totalMessages > 0 ? round(($msgStatusCounts['traite'] / $totalMessages) * 100) : 0;

    $counts = [
        'programs' => (int) $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn(),
        'partners' => (int) $pdo->query('SELECT COUNT(*) FROM partners')->fetchColumn(),
        'news'     => (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    ];
    $recentNews = $pdo->query('SELECT * FROM news ORDER BY created_at DESC LIMIT 5')->fetchAll();
    ?>
    <div class="mb-2">
        <p class="text-muted small">
            <i class="bi bi-info-circle"></i> Synthèse d'activité globale du site — le système ne rattache pas encore les actions à un employé précis, cette vue reste donc générale plutôt que nominative.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="text-muted small mb-1">Messages traités</div>
                <div class="fs-3 fw-bold text-success"><?= $treatedRate ?>%</div>
                <div class="small text-muted"><?= $msgStatusCounts['traite'] ?> sur <?= $totalMessages ?> message<?= $totalMessages > 1 ? 's' : '' ?> au total</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="text-muted small mb-1">En cours de traitement</div>
                <div class="fs-3 fw-bold text-warning"><?= $msgStatusCounts['en_cours'] ?></div>
                <div class="small text-muted">Suivi actif par l'équipe</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="text-muted small mb-1">Pas encore gérés</div>
                <div class="fs-3 fw-bold text-danger"><?= $msgStatusCounts['non_traite'] ?></div>
                <div class="small text-muted">En attente d'une réponse</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card p-3">
                <div class="text-muted small">Filières</div>
                <div class="fs-4 fw-bold"><?= $counts['programs'] ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card p-3">
                <div class="text-muted small">Institutions partenaires</div>
                <div class="fs-4 fw-bold"><?= $counts['partners'] ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card p-3">
                <div class="text-muted small">Actualités publiées</div>
                <div class="fs-4 fw-bold"><?= $counts['news'] ?></div>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Dernières actualités ajoutées</h6>
            <a href="<?= BASE_URL ?>/admin/news/form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Ajouter une actualité</a>
        </div>
        <?php if (!$recentNews): ?>
            <p class="text-muted mb-0">Aucune actualité pour le moment.</p>
        <?php else: ?>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Titre</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentNews as $n): ?>
                    <tr>
                        <td><?= e($n['title']) ?></td>
                        <td><?= e($n['created_at']) ?></td>
                        <td><a href="<?= BASE_URL ?>/admin/news/form.php?id=<?= (int) $n['id'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <a href="<?= BASE_URL ?>/admin/messages/list.php" class="btn btn-outline-primary">Voir le suivi complet des messages →</a>
    </div>

<?php
// =========================================================
// SUPER-ADMIN — tableau de bord général existant.
// =========================================================
else:
    $counts = [
        'programs'     => $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn(),
        'partners'     => $pdo->query('SELECT COUNT(*) FROM partners')->fetchColumn(),
        'news'         => $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
        'testimonials' => $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn(),
    ];
    $counts['unread_msgs'] = $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    $recentMessages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5')->fetchAll();
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="text-muted small">Programmes</div>
                <div class="fs-3 fw-bold"><?= (int) $counts['programs'] ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="text-muted small">Institutions partenaires</div>
                <div class="fs-3 fw-bold"><?= (int) $counts['partners'] ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="text-muted small">Avis clients</div>
                <div class="fs-3 fw-bold"><?= (int) $counts['testimonials'] ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="text-muted small">Messages non lus</div>
                <div class="fs-3 fw-bold text-danger"><?= (int) $counts['unread_msgs'] ?></div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <h6 class="mb-3">Derniers messages reçus</h6>
        <?php if (!$recentMessages): ?>
            <p class="text-muted mb-0">Aucun message pour le moment.</p>
        <?php else: ?>
            <table class="table table-sm align-middle">
                <thead><tr><th>Nom</th><th>Sujet</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentMessages as $m): ?>
                    <tr>
                        <td><?= e($m['full_name']) ?></td>
                        <td><?= e($m['subject']) ?></td>
                        <td><?= e($m['created_at']) ?></td>
                        <td><a href="<?= BASE_URL ?>/admin/messages/view.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
