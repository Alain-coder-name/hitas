<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$partner = ['name' => '', 'country' => '', 'flag_emoji' => '', 'description' => '', 'specialties' => '', 'website' => '', 'logo' => '', 'is_published' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM partners WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Partenaire introuvable.'); }
    $partner = $found;

    $stmt2 = $pdo->prepare('SELECT program_id FROM program_partners WHERE partner_id = ?');
    $stmt2->execute([$id]);
    $linkedProgramIds = array_column($stmt2->fetchAll(), 'program_id');

    // Cycles (catégories) de ce partenaire, pour le lien précis optionnel
    // vers la page de ce cycle chez lui (voir partenaire.php > cycleUrl).
    $stmt3 = $pdo->prepare("SELECT DISTINCT pr.category FROM programs pr
        JOIN program_partners pp ON pp.program_id = pr.id
        WHERE pp.partner_id = ? AND pr.category IS NOT NULL AND pr.category <> ''
        ORDER BY pr.category");
    $stmt3->execute([$id]);
    $partnerCycles = array_column($stmt3->fetchAll(), 'category');

    $stmt4 = $pdo->prepare('SELECT category, url FROM partner_cycle_links WHERE partner_id = ?');
    $stmt4->execute([$id]);
    $existingCycleLinks = array_column($stmt4->fetchAll(), 'url', 'category');
} else {
    $linkedProgramIds = [];
    $partnerCycles = [];
    $existingCycleLinks = [];
}

$allPrograms = $pdo->query('SELECT id, title FROM programs ORDER BY title')->fetchAll();

$pageTitle = $id ? 'Modifier un partenaire' : 'Ajouter un partenaire';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/partners/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nom de l'institution / établissement</label>
            <input type="text" name="name" class="form-control" value="<?= e($partner['name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Pays</label>
            <input type="text" name="country" class="form-control" value="<?= e($partner['country']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Niveau du diplôme <span class="text-muted fw-normal">(optionnel)</span></label>
            <input type="text" name="degree_level" class="form-control" placeholder="Ex : Bachelor, Master, Licence..." value="<?= e($partner['degree_level'] ?? '') ?>">
            <div class="form-text">Pour préciser à quel(s) niveau(x) ce partenaire accueille les étudiants HITAS — affiché sur sa fiche.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Drapeau du pays</label>
            <div class="flag-picker">
                <div class="flag-picker-input-row">
                    <div class="flag-picker-preview"><span class="flag-picker-preview-emoji"><?= e($partner['flag_emoji'] ?? '') ?></span></div>
                    <input type="text" class="form-control flag-picker-search" placeholder="Tapez le nom du pays..." autocomplete="off">
                </div>
                <input type="hidden" name="flag_emoji" class="flag-picker-value" value="<?= e($partner['flag_emoji'] ?? '') ?>">
                <ul class="flag-picker-list" hidden></ul>
            </div>
            <div class="form-text">Tapez quelques lettres, le drapeau apparaît automatiquement — plus besoin de copier-coller un emoji.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= e($partner['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Spécialités / offres particulières <span class="text-muted fw-normal">(optionnel)</span></label>
            <textarea name="specialties" class="form-control" rows="3" placeholder="Une par ligne, ex : Master en logistique&#10;Certificat en tourisme"><?= e($partner['specialties'] ?? '') ?></textarea>
            <div class="form-text">Pour les partenaires qui ne proposent pas de filières HITAS cochables ci-dessous — décrivez plutôt librement ce qu'ils offrent.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Diplôme ou co-diplomation via HITAS <span class="text-muted fw-normal">(optionnel)</span></label>
            <textarea name="co_diplomation_note" class="form-control" rows="3" placeholder="À remplir uniquement si HITAS délivre ses diplômes via ce partenaire : précisez la durée du parcours (préparation HITAS + cycle chez le partenaire)."><?= e($partner['co_diplomation_note'] ?? '') ?></textarea>
            <div class="form-text">Si rempli, ce texte s'affiche en évidence sur la fiche du partenaire, et le bloc « Diplôme ou co-diplômation » de la page d'accueil pointera automatiquement vers ce partenaire.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Site web</label>
            <input type="url" name="website" class="form-control" placeholder="https://" value="<?= e($partner['website']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="is_published" class="form-select">
                <option value="1" <?= $partner['is_published'] ? 'selected' : '' ?>>Publié</option>
                <option value="0" <?= !$partner['is_published'] ? 'selected' : '' ?>>Brouillon</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Logo</label>
            <?php if (!empty($partner['logo'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>partners/<?= e($partner['logo']) ?>" class="thumb-preview" style="width:100px;height:100px;"></div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="mb-4">
            <label class="form-label">Filières proposées par cet établissement</label>
            <div class="border rounded p-3" style="max-height: 260px; overflow-y:auto;">
                <?php if (!$allPrograms): ?>
                    <p class="text-muted mb-0">Aucune filière enregistrée pour le moment.</p>
                <?php endif; ?>
                <?php foreach ($allPrograms as $program): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="program_ids[]"
                               value="<?= (int)$program['id'] ?>" id="program_<?= (int)$program['id'] ?>"
                               <?= in_array($program['id'], $linkedProgramIds) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="program_<?= (int)$program['id'] ?>">
                            <?= e($program['title']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text">Cochez toutes les filières que cet établissement propose — c'est ce qui permet à la recherche de la page Programmes de le retrouver.</div>
        </div>

        <?php if ($partnerCycles): ?>
            <div class="mb-4">
                <label class="form-label">Liens précis par cycle <span class="text-muted fw-normal">(optionnel)</span></label>
                <div class="border rounded p-3">
                    <?php foreach ($partnerCycles as $cycle): ?>
                        <div class="mb-2">
                            <label class="form-label small mb-1"><?= e($cycle) ?></label>
                            <input type="url" name="cycle_url[<?= e($cycle) ?>]" class="form-control form-control-sm"
                                   placeholder="Page de ce cycle chez ce partenaire (optionnel — sinon son site officiel)"
                                   value="<?= e($existingCycleLinks[$cycle] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Sur la fiche de ce partenaire, le titre de chaque cycle (ex : « Ingénierie & Technologies ») pointe vers ce lien précis s'il est renseigné, sinon vers le site officiel du partenaire.</div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/partners/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
