<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/media_library.php';
requireLogin();

$q         = trim($_GET['q'] ?? '');
$folder    = trim($_GET['folder'] ?? '');
$type      = trim($_GET['type'] ?? '');   // image | video | other
$usage     = trim($_GET['usage'] ?? '');  // used | orphan

$allFiles = mediaScanFiles();

$files = array_filter($allFiles, function ($f) use ($q, $folder, $type, $usage) {
    if ($q !== '' && stripos($f['filename'], $q) === false) return false;
    if ($folder !== '' && $f['folder'] !== $folder) return false;
    if ($type !== '' && $f['kind'] !== $type) return false;
    if ($usage === 'used' && $f['isOrphan']) return false;
    if ($usage === 'orphan' && !$f['isOrphan']) return false;
    return true;
});

// Pour chaque fichier orphelin, cherche à l'avance tous les emplacements du
// site qui acceptent son type (image/vidéo), pour savoir s'il faut afficher
// le bouton « Réutiliser ». La liste ne dépend que du type de fichier (plus
// du dossier), donc calculée une seule fois par type plutôt que par fichier.
$reuseTargetsByKind = [];
$reuseTargetsByKey = [];
foreach ($allFiles as $f) {
    if (!$f['isOrphan']) {
        continue;
    }
    if (!isset($reuseTargetsByKind[$f['kind']])) {
        $reuseTargetsByKind[$f['kind']] = mediaReusableTargets($f['kind']);
    }
    $reuseTargetsByKey[$f['key']] = $reuseTargetsByKind[$f['kind']];
}

$totalCount   = count($allFiles);
$totalSize    = array_sum(array_column($allFiles, 'size'));
$orphanCount  = count(array_filter($allFiles, fn($f) => $f['isOrphan']));

$pageTitle = 'Galerie & Médiathèque';
include __DIR__ . '/../header.php';
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/admin/gallery/list.php"><i class="bi bi-images"></i> Galerie (photos vie étudiante)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?= BASE_URL ?>/admin/gallery/media.php"><i class="bi bi-hdd-stack"></i> Médiathèque (tous les fichiers)</a>
    </li>
</ul>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fs-4 fw-bold"><?= (int)$totalCount ?></div>
            <div class="small text-muted">Fichiers au total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fs-4 fw-bold"><?= e(mediaHumanSize((int)$totalSize)) ?></div>
            <div class="small text-muted">Espace utilisé</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fs-4 fw-bold text-warning"><?= (int)$orphanCount ?></div>
            <div class="small text-muted">Fichiers orphelins (non utilisés)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center d-flex align-items-center justify-content-center">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload"></i> Ajouter un fichier
            </button>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Rechercher un fichier</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="nom du fichier..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Dossier</label>
            <select name="folder" class="form-select form-select-sm">
                <option value="">Tous les dossiers</option>
                <?php foreach (mediaKnownFolders() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $folder === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Tous</option>
                <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Vidéos</option>
                <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Autres</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Utilisation</label>
            <select name="usage" class="form-select form-select-sm">
                <option value="">Tous</option>
                <option value="used" <?= $usage === 'used' ? 'selected' : '' ?>>Utilisés</option>
                <option value="orphan" <?= $usage === 'orphan' ? 'selected' : '' ?>>Orphelins</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<div class="row g-3">
    <?php foreach ($files as $f): ?>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-2 h-100">
                <div class="ratio ratio-1x1 mb-2 bg-light rounded overflow-hidden d-flex align-items-center justify-content-center">
                    <?php if ($f['kind'] === 'image'): ?>
                        <img src="<?= e($f['url']) ?>" class="w-100 h-100" style="object-fit:cover;" loading="lazy">
                    <?php elseif ($f['kind'] === 'video'): ?>
                        <i class="bi bi-camera-reels text-secondary" style="font-size:2.5rem;"></i>
                    <?php else: ?>
                        <i class="bi bi-file-earmark text-secondary" style="font-size:2.5rem;"></i>
                    <?php endif; ?>
                </div>
                <div class="small text-truncate fw-semibold" title="<?= e($f['filename']) ?>"><?= e($f['filename']) ?></div>
                <div class="small text-muted mb-1"><?= e($f['folderLabel']) ?> · <?= e(mediaHumanSize($f['size'])) ?></div>

                <?php if ($f['isOrphan']): ?>
                    <span class="badge bg-warning text-dark mb-2 align-self-start"><i class="bi bi-exclamation-triangle"></i> Orphelin</span>
                <?php else: ?>
                    <button type="button" class="badge bg-success border-0 mb-2 align-self-start" data-bs-toggle="modal" data-bs-target="#usageModal-<?= md5($f['key']) ?>">
                        <i class="bi bi-link-45deg"></i> Utilisé (<?= count($f['usages']) ?>)
                    </button>
                <?php endif; ?>

                <div class="d-flex flex-column gap-1 mt-auto">
                    <?php if ($f['isOrphan'] && !empty($reuseTargetsByKey[$f['key']])): ?>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#reuseModal-<?= md5($f['key']) ?>">
                            <i class="bi bi-arrow-return-left"></i> Réutiliser
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#replaceModal-<?= md5($f['key']) ?>">
                        <i class="bi bi-arrow-repeat"></i> Remplacer
                    </button>
                    <form action="<?= BASE_URL ?>/admin/gallery/media_delete.php" method="post" class="js-confirm-delete-media" data-used="<?= $f['isOrphan'] ? '0' : '1' ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="folder" value="<?= e($f['folder']) ?>">
                        <input type="hidden" name="filename" value="<?= e($f['filename']) ?>">
                        <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Supprimer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modale : détail des usages -->
        <?php if (!$f['isOrphan']): ?>
        <div class="modal fade" id="usageModal-<?= md5($f['key']) ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Utilisations de « <?= e($f['filename']) ?> »</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($f['usages'] as $u): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small fw-semibold"><?= e($u['label']) ?></div>
                                        <?php if ($u['title']): ?><div class="small text-muted"><?= e($u['title']) ?></div><?php endif; ?>
                                    </div>
                                    <a href="<?= e($u['editUrl']) ?>" class="btn btn-sm btn-outline-primary">Ouvrir</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modale : remplacement du fichier -->
        <div class="modal fade" id="replaceModal-<?= md5($f['key']) ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= BASE_URL ?>/admin/gallery/media_replace.php" method="post" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h6 class="modal-title">Remplacer « <?= e($f['filename']) ?> »</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="folder" value="<?= e($f['folder']) ?>">
                            <input type="hidden" name="filename" value="<?= e($f['filename']) ?>">
                            <p class="small text-muted">Le nouveau fichier remplace l'ancien à l'identique (même emplacement, même nom). Toutes les pages qui l'utilisent seront donc mises à jour automatiquement — <?= $f['isOrphan'] ? 'aucune page ne l\'utilise actuellement.' : ('actuellement utilisé à ' . count($f['usages']) . ' endroit(s).') ?></p>
                            <input type="file" name="new_file" class="form-control" required accept="<?= $f['kind'] === 'video' ? '.mp4,.webm' : '.jpg,.jpeg,.png,.webp,.gif' ?>">
                            <div class="form-text"><?= $f['kind'] === 'video' ? 'Formats acceptés : mp4, webm.' : 'Formats acceptés : jpg, jpeg, png, webp, gif.' ?></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button class="btn btn-primary">Remplacer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modale : réutilisation d'un fichier orphelin sur un contenu existant -->
        <?php if ($f['isOrphan'] && !empty($reuseTargetsByKey[$f['key']])): ?>
        <div class="modal fade" id="reuseModal-<?= md5($f['key']) ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?= BASE_URL ?>/admin/gallery/media_reuse.php" method="post">
                        <div class="modal-header">
                            <h6 class="modal-title">Réutiliser « <?= e($f['filename']) ?> »</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="folder" value="<?= e($f['folder']) ?>">
                            <input type="hidden" name="filename" value="<?= e($f['filename']) ?>">
                            <p class="small text-muted">Associez ce fichier à un contenu existant : il redevient actif sur le site, à cet emplacement. Un emplacement marqué « occupé » a déjà une image — la choisir la remplacera par ce fichier.</p>
                            <label class="form-label small mb-1">Où réutiliser ce fichier ?</label>
                            <select name="target" class="form-select reuse-target-select" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($reuseTargetsByKey[$f['key']] as $group): ?>
                                    <optgroup label="<?= e($group['label']) ?>">
                                        <?php foreach ($group['options'] as $opt): ?>
                                            <option value="<?= e($opt['value']) ?>">
                                                <?= e($opt['label']) ?><?= $opt['occupied'] ? ' (occupé — sera remplacé)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>

                            <?php foreach ($reuseTargetsByKey[$f['key']] as $group): ?>
                                <?php if (!empty($group['createFields'])): ?>
                                    <div class="reuse-create-block border rounded p-3 mt-3 d-none" data-value="<?= e($group['createValue']) ?>">
                                        <p class="small fw-semibold mb-2"><?= e($group['label']) ?> — nouvelle fiche</p>
                                        <?php foreach ($group['createFields'] as $field): ?>
                                            <div class="mb-2">
                                                <label class="form-label small mb-1"><?= e($field['label']) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
                                                <?php if ($field['type'] === 'textarea'): ?>
                                                    <textarea name="create_<?= e($field['column']) ?>" class="form-control form-control-sm" rows="3"></textarea>
                                                <?php elseif ($field['type'] === 'select'): ?>
                                                    <select name="create_<?= e($field['column']) ?>" class="form-select form-select-sm">
                                                        <option value="">— Choisir —</option>
                                                        <?php foreach ($field['options'] as $optValue => $optLabel): ?>
                                                            <option value="<?= e($optValue) ?>"><?= e($optLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="text" name="create_<?= e($field['column']) ?>" class="form-control form-control-sm">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <p class="small text-muted mb-0">La fiche est créée avec cette image ; vous pourrez compléter et publier le reste juste après.</p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button class="btn btn-success">Réutiliser</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!$files): ?>
        <p class="text-muted">Aucun fichier ne correspond à ces critères.</p>
    <?php endif; ?>
</div>

<!-- Modale : upload d'un nouveau fichier -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/admin/gallery/media_upload.php" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h6 class="modal-title">Ajouter un fichier à la médiathèque</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="mb-3">
                        <label class="form-label small">Dossier de destination</label>
                        <select name="folder" class="form-select" required>
                            <?php foreach (mediaKnownFolders() as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Fichier (image ou vidéo)</label>
                        <input type="file" name="new_file" class="form-control" required accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm">
                    </div>
                    <div class="form-text">Ce fichier sera disponible dans la médiathèque mais ne sera relié à aucun contenu tant que vous ne le sélectionnez pas depuis un formulaire (Programmes, Actualités...).</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.js-confirm-delete-media').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var used = form.getAttribute('data-used') === '1';
        var msg = used
            ? "Ce fichier est utilisé ailleurs sur le site : la suppression sera refusée tant qu'il est référencé. Continuer quand même ?"
            : "Confirmer la suppression définitive de ce fichier ?";
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });
});

// Affiche le bloc de saisie "nouvelle fiche" correspondant à l'option
// choisie dans une modale de réutilisation, masque les autres.
document.querySelectorAll('.reuse-target-select').forEach(function (select) {
    var body = select.closest('.modal-body');
    if (!body) return;
    var blocks = body.querySelectorAll('.reuse-create-block');
    select.addEventListener('change', function () {
        blocks.forEach(function (block) {
            block.classList.toggle('d-none', block.dataset.value !== select.value);
        });
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>
