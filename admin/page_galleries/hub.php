<?php
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pdo = getDB();
$galleries = [
    'a-propos'             => ['label' => 'Photos — À propos', 'desc' => 'Diaporama affiché à côté du texte principal de la page À propos.'],
    'mission'               => ['label' => 'Photos — Notre mission', 'desc' => 'Diaporama affiché à côté du texte de la section Notre mission.'],
    'admission-conditions'  => ['label' => 'Photos — Conditions d\'admission', 'desc' => 'Diaporama affiché à gauche du texte, sur la section Conditions d\'admission (page FAQ).'],
];

$counts = [];
foreach (array_keys($galleries) as $key) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM page_galleries WHERE page_key = ?');
    $stmt->execute([$key]);
    $counts[$key] = (int) $stmt->fetchColumn();
}

$pageTitle = 'Galeries photo (diaporamas)';
include __DIR__ . '/../header.php';
?>

<div class="mb-3">
    <h5 class="mb-1">Galeries photo (diaporamas)</h5>
    <p class="text-muted small mb-0">Ajoutez plusieurs photos à chacun de ces emplacements — elles défileront automatiquement en diaporama sur le site public. Une seule photo suffit à activer le diaporama ; sans aucune photo, l'emplacement garde son apparence par défaut.</p>
</div>

<div class="row g-3">
    <?php foreach ($galleries as $key => $info): ?>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h6 class="mb-2"><?= e($info['label']) ?></h6>
                <p class="text-muted small mb-3"><?= e($info['desc']) ?></p>
                <p class="mb-3"><span class="badge bg-primary"><?= $counts[$key] ?> photo<?= $counts[$key] > 1 ? 's' : '' ?></span></p>
                <a href="<?= BASE_URL ?>/admin/page_galleries/list.php?key=<?= urlencode($key) ?>" class="btn btn-outline-primary btn-sm mt-auto">Gérer les photos →</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
