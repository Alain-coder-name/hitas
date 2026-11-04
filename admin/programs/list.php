<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$programs = $pdo->query('SELECT * FROM programs ORDER BY is_featured DESC, featured_order ASC, created_at DESC')->fetchAll();
$featuredCount = 0;
foreach ($programs as $p) { if ($p['is_featured']) { $featuredCount++; } }

// Regroupement obligatoire : Niveau (Bachelor / Master) -> Groupe (l'un
// des 6 groupes officiels, voir includes/filiere_classifier.php) ->
// Spécialité, triée par ordre alphabétique A -> Z à l'intérieur de chaque
// groupe.
$niveaux = ['Bachelor', 'Master'];
$grouped = [];
$unclassified = [];
foreach ($programs as $p) {
    $niveau = in_array($p['degree_level'], $niveaux, true) ? $p['degree_level'] : null;
    $filiere = trim((string)($p['category'] ?? ''));
    if ($niveau === null || $filiere === '') {
        $unclassified[] = $p;
        continue;
    }
    $grouped[$niveau][$filiere][] = $p;
}
foreach ($grouped as $niveau => &$filieres) {
    ksort($filieres, SORT_STRING | SORT_FLAG_CASE);
    foreach ($filieres as &$specialites) {
        usort($specialites, fn($a, $b) => strcasecmp($a['title'], $b['title']));
    }
    unset($specialites);
}
unset($filieres);

$pageTitle = 'Filières';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Filières</h5>
        <p class="text-muted small mb-0">
            <?= $featuredCount ?> filière<?= $featuredCount > 1 ? 's' : '' ?> phare<?= $featuredCount > 1 ? 's' : '' ?> actuellement mise<?= $featuredCount > 1 ? 's' : '' ?> en avant sur l'Accueil et en tête de la page Programmes.
            <?php if (!$featuredCount): ?>
                <span class="text-danger">Aucune pour l'instant — cochez « Phare » ci-dessous pour vos filières les plus demandées.</span>
            <?php endif; ?>
            Organisées par Niveau (Bachelor / Master) puis par Groupe (Ingénierie & Technologies, Management & Commerce...), spécialités classées de A à Z.
        </p>
    </div>
    <a href="<?= BASE_URL ?>/admin/programs/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter une filière</a>
</div>

<?php
function renderProgramsTable(array $rows): void {
?>
<table class="table align-middle">
    <thead>
    <tr><th>Image</th><th>Titre</th><th style="width:170px;">Phare</th><th>Statut</th><th style="width:140px;"></th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
        <tr>
            <td>
                <?php if ($p['image']): ?>
                    <img src="<?= UPLOAD_URL ?>programs/<?= e($p['image']) ?>" class="thumb-preview">
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td><?= e($p['title']) ?></td>
            <td>
                <div class="form-check form-switch mb-0 featured-toggle" data-id="<?= (int)$p['id'] ?>">
                    <input class="form-check-input" type="checkbox" role="switch" <?= $p['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label small"><i class="bi bi-star-fill text-warning"></i> Phare<?= $p['is_featured'] ? ' (ordre ' . (int)$p['featured_order'] . ')' : '' ?></label>
                </div>
            </td>
            <td>
                <?= $p['is_published']
                    ? '<span class="badge bg-success">Publié</span>'
                    : '<span class="badge bg-secondary">Brouillon</span>' ?>
            </td>
            <td class="text-end">
                <a href="<?= BASE_URL ?>/admin/programs/form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form action="<?= BASE_URL ?>/admin/programs/delete.php" method="post" class="d-inline js-confirm-delete">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
}
?>

<?php foreach ($niveaux as $niveau): ?>
    <?php if (!empty($grouped[$niveau])): ?>
        <div class="card p-3 mb-3">
            <h5 class="mb-3"><?= e($niveau) ?></h5>
            <?php foreach ($grouped[$niveau] as $filiere => $specialites): ?>
                <h6 class="text-muted mb-2 mt-3"><?= e($filiere) ?></h6>
                <?php renderProgramsTable($specialites); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($unclassified): ?>
    <div class="card p-3 mb-3">
        <h5 class="mb-1">Non classées</h5>
        <p class="text-muted small mb-3">Il manque le Niveau et/ou la Filière — modifiez ces filières pour les ranger dans la structure.</p>
        <?php renderProgramsTable($unclassified); ?>
    </div>
<?php endif; ?>

<?php if (!$programs): ?>
    <div class="card p-3 text-center text-muted py-4">Aucune filière pour le moment.</div>
<?php endif; ?>

<script>
    var csrfToken = <?= json_encode(csrfToken()) ?>;
    document.querySelectorAll('.featured-toggle input').forEach(function (input) {
        input.addEventListener('change', function () {
            var wrapper = input.closest('.featured-toggle');
            var id = wrapper.dataset.id;
            fetch('<?= BASE_URL ?>/admin/programs/toggle_featured.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken, id: id, is_featured: input.checked ? 1 : 0 })
            }).then(function () {
                // Recharge pour tenir à jour le compteur et l'ordre d'affichage.
                window.location.reload();
            }).catch(function () {});
        });
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>
