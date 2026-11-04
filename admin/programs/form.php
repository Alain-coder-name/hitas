<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/filiere_classifier.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$program = ['title' => '', 'short_description' => '', 'description' => '', 'duration' => '', 'degree_level' => '', 'category' => '', 'prerequisites' => '', 'outcomes' => '', 'image' => '', 'is_published' => 1, 'is_featured' => 0, 'featured_order' => 0];
$linkedPartnerIds = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Filière introuvable.'); }
    $program = $found;

    $stmt2 = $pdo->prepare('SELECT partner_id, partner_url FROM program_partners WHERE program_id = ?');
    $stmt2->execute([$id]);
    $linkedPartnerRows = $stmt2->fetchAll();
    $linkedPartnerIds = array_column($linkedPartnerRows, 'partner_id');
    $linkedPartnerUrls = array_column($linkedPartnerRows, 'partner_url', 'partner_id');
}
$linkedPartnerUrls ??= [];

$allPartners = $pdo->query('SELECT id, name, country FROM partners ORDER BY name')->fetchAll();

$pageTitle = $id ? 'Modifier une filière' : 'Ajouter une filière';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <form action="<?= BASE_URL ?>/admin/programs/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Titre de la filière</label>
            <input type="text" name="title" class="form-control" value="<?= e($program['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description courte (résumé affiché en liste)</label>
            <textarea name="short_description" class="form-control" rows="2"><?= e($program['short_description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Description complète de la filière</label>
            <textarea name="description" class="form-control" rows="8"><?= e($program['description']) ?></textarea>
            <div class="form-text">Décrivez bien la filière : ce qu'on y apprend, débouchés, spécificités. Ce texte s'affiche sur la fiche détaillée de la filière.</div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Durée</label>
                <input type="text" name="duration" class="form-control" placeholder="Ex : 1 an" value="<?= e($program['duration']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Niveau</label>
                <input type="text" name="degree_level" class="form-control" list="degreeLevelOptions"
                       value="<?= e($program['degree_level'] ?? '') ?>" placeholder="Ex : Bachelor, Master, DUT...">
                <datalist id="degreeLevelOptions">
                    <?php foreach ([
                        'Programme Foundation', 'BTS', 'DUT', 'Licence', 'Bachelor', 'Licence Professionnelle',
                        "Diplôme d'Ingénieur des Travaux", "Diplôme d'Ingénieur", "Programme Pre-Master's",
                        'Master', 'Doctorat',
                    ] as $levelOption): ?>
                        <option value="<?= e($levelOption) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Choisissez un niveau existant dans la liste, ou tapez-en un nouveau librement (ex : Diplôme d'Ingénieur, Doctorat en Médecine Vétérinaire...).</div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Statut</label>
                <select name="is_published" class="form-select">
                    <option value="1" <?= $program['is_published'] ? 'selected' : '' ?>>Publié</option>
                    <option value="0" <?= !$program['is_published'] ? 'selected' : '' ?>>Brouillon</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7 mb-3">
                <label class="form-label">Groupe</label>
                <select name="category" id="categoryGroupSelect" class="form-select">
                    <option value="">— Choisir —</option>
                    <?php foreach (filiereGroups() as $groupOption): ?>
                        <option value="<?= e($groupOption) ?>" <?= ($program['category'] ?? '') === $groupOption ? 'selected' : '' ?>><?= e($groupOption) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Un seul groupe parmi les 6 officiels. Cette spécialité doit appartenir à un seul groupe.</div>

                <div id="groupSuggestionBox" class="mt-2 p-2 border rounded bg-light small" hidden>
                    <div><strong>Suggestion IA :</strong> <span id="groupSuggestionText"></span></div>
                    <div class="text-muted" id="groupSuggestionConfidence"></div>
                    <div class="mt-1 d-flex gap-2 flex-wrap" id="groupSuggestionActions"></div>
                </div>
            </div>
        </div>

        <div class="row align-items-end">
            <div class="col-md-7 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" <?= $program['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isFeatured">Filière phare</label>
                </div>
                <div class="form-text">À cocher pour les filières très demandées — affichées en priorité sur l'Accueil et en tête de la page Programmes. Ce ne sont pas forcément toutes vos filières, seulement celles à mettre en avant.</div>
            </div>
            <div class="col-md-5 mb-3">
                <label class="form-label">Ordre parmi les filières phares</label>
                <input type="number" name="featured_order" class="form-control" value="<?= (int)$program['featured_order'] ?>">
                <div class="form-text">Plus petit = affiché en premier.</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Prérequis</label>
            <textarea name="prerequisites" class="form-control" rows="3"><?= e($program['prerequisites']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Débouchés</label>
            <textarea name="outcomes" class="form-control" rows="4"><?= e($program['outcomes'] ?? '') ?></textarea>
            <div class="form-text">Métiers, secteurs ou poursuites d'études accessibles après cette filière. Affiché sur la fiche publique de la filière.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Image de la filière</label>
            <?php if (!empty($program['image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>programs/<?= e($program['image']) ?>" class="thumb-preview" style="width:100px;height:100px;"></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="mb-4">
            <label class="form-label">Institutions partenaires associées</label>
            <div class="border rounded p-3" style="max-height: 320px; overflow-y:auto;">
                <?php if (!$allPartners): ?>
                    <p class="text-muted mb-0">Aucun partenaire enregistré pour le moment.</p>
                <?php endif; ?>
                <?php foreach ($allPartners as $partner): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="partner_ids[]"
                               value="<?= (int)$partner['id'] ?>" id="partner_<?= (int)$partner['id'] ?>"
                               <?= in_array($partner['id'], $linkedPartnerIds) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="partner_<?= (int)$partner['id'] ?>">
                            <?= e($partner['name']) ?> <span class="text-muted">(<?= e($partner['country']) ?>)</span>
                        </label>
                        <input type="url" name="partner_url[<?= (int)$partner['id'] ?>]" class="form-control form-control-sm mt-1 ms-4"
                               style="max-width: 480px;" placeholder="Lien précis chez ce partenaire (optionnel — sinon son site officiel)"
                               value="<?= e($linkedPartnerUrls[$partner['id']] ?? '') ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text">Le lien précis (optionnel) emmène directement vers la page de cette filière chez ce partenaire. Sans lien précis, le clic renvoie vers le site officiel du partenaire.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/programs/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<script>
// Aide à la classification des filières : analyse le titre saisi et
// suggère un groupe parmi les 6 officiels. Ne modifie JAMAIS le champ
// Groupe toute seule — l'administrateur clique toujours lui-même sur
// « Accepter » ou choisit un autre groupe dans la liste déroulante.
(function () {
    var titleInput = document.querySelector('input[name="title"]');
    var groupSelect = document.getElementById('categoryGroupSelect');
    var box = document.getElementById('groupSuggestionBox');
    var textEl = document.getElementById('groupSuggestionText');
    var confEl = document.getElementById('groupSuggestionConfidence');
    var actionsEl = document.getElementById('groupSuggestionActions');
    if (!titleInput || !groupSelect || !box) return;

    var STORAGE_KEY = 'hitas_filiere_group_corrections';
    var CONFIDENCE_LABELS = {
        high: 'Confiance élevée',
        medium: 'Confiance moyenne — merci de confirmer',
        low: 'Groupe non déterminé — veuillez sélectionner un groupe'
    };

    function normalize(text) {
        return text.toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim().replace(/\s+/g, ' ');
    }

    // Mémoire locale des corrections de l'administrateur (LocalStorage,
    // propre à ce poste/navigateur) : si un nom déjà corrigé une fois
    // revient, on propose directement le groupe choisi la dernière fois.
    function loadCorrections() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch (e) { return {}; }
    }
    function saveCorrection(name, group) {
        try {
            var store = loadCorrections();
            store[normalize(name)] = group;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
        } catch (e) { /* LocalStorage indisponible : on continue sans mémoriser */ }
    }

    var lastSuggestedGroup = null;
    var debounceTimer = null;

    function chooseGroup(group, isCorrection) {
        groupSelect.value = group;
        if (isCorrection && titleInput.value.trim()) {
            saveCorrection(titleInput.value, group);
        }
    }

    function renderSuggestion(group, confidence, alternatives, learned) {
        lastSuggestedGroup = group;
        actionsEl.innerHTML = '';

        if (!group) {
            textEl.textContent = '—';
            confEl.textContent = CONFIDENCE_LABELS.low;
            box.hidden = false;
            return;
        }

        textEl.textContent = '✓ ' + group + (learned ? ' (appris de vos corrections précédentes)' : '');
        confEl.textContent = CONFIDENCE_LABELS[confidence] || '';

        var acceptBtn = document.createElement('button');
        acceptBtn.type = 'button';
        acceptBtn.className = 'btn btn-sm btn-success';
        acceptBtn.textContent = 'Accepter la suggestion';
        acceptBtn.addEventListener('click', function () { chooseGroup(group, false); });
        actionsEl.appendChild(acceptBtn);

        (alternatives || []).forEach(function (alt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary';
            btn.textContent = alt;
            btn.addEventListener('click', function () { chooseGroup(alt, true); });
            actionsEl.appendChild(btn);
        });

        box.hidden = false;
    }

    function analyze(name) {
        if (!name.trim()) { box.hidden = true; lastSuggestedGroup = null; return; }

        var corrections = loadCorrections();
        var key = normalize(name);
        if (corrections[key]) {
            renderSuggestion(corrections[key], 'high', [], true);
            return;
        }

        fetch('<?= BASE_URL ?>/admin/programs/suggest_group.php?name=' + encodeURIComponent(name))
            .then(function (r) { return r.json(); })
            .then(function (data) { renderSuggestion(data.group, data.confidence, data.alternatives, false); })
            .catch(function () { box.hidden = true; });
    }

    titleInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var value = titleInput.value;
        debounceTimer = setTimeout(function () { analyze(value); }, 400);
    });

    // En mode modification, propose déjà une suggestion si le champ Groupe
    // n'a pas encore été renseigné pour cette filière.
    if (titleInput.value.trim() && !groupSelect.value) {
        analyze(titleInput.value);
    }

    // Si l'administrateur change lui-même le groupe (liste déroulante) après
    // avoir vu une suggestion, et que son choix diffère de celle-ci, on
    // mémorise cette correction pour les prochaines saisies similaires.
    groupSelect.addEventListener('change', function () {
        if (lastSuggestedGroup && groupSelect.value && groupSelect.value !== lastSuggestedGroup && titleInput.value.trim()) {
            saveCorrection(titleInput.value, groupSelect.value);
        }
    });
})();
</script>

<?php include __DIR__ . '/../footer.php'; ?>
