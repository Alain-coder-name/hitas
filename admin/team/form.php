<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$member = ['full_name' => '', 'role' => '', 'tier' => 'bronze', 'contact_email' => '', 'contact_whatsapp' => '', 'bio' => '', 'verify_link' => '', 'photo' => '', 'order_index' => 0, 'is_published' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM team_members WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Membre introuvable.'); }
    $member = $found;
}

$pageTitle = $id ? 'Modifier un membre' : 'Ajouter un membre';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/team/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nom complet</label>
            <input type="text" name="full_name" class="form-control" value="<?= e($member['full_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Fonction</label>
            <input type="text" name="role" class="form-control" value="<?= e($member['role']) ?>" placeholder="Ex : Président, Registraire, Agent d'admission...">
        </div>

        <div class="mb-3">
            <label class="form-label">Catégorie</label>
            <select name="tier" class="form-select" id="tierSelect">
                <option value="gold" <?= $member['tier'] === 'gold' ? 'selected' : '' ?>>🥇 Or — Direction (Président et adjoints)</option>
                <option value="silver" <?= $member['tier'] === 'silver' ? 'selected' : '' ?>>🥈 Argent — Responsables (registraire, chefs de service...)</option>
                <option value="bronze" <?= $member['tier'] === 'bronze' ? 'selected' : '' ?>>🥉 Bronze — Employés</option>
            </select>
            <div class="form-text">
                Détermine comment le membre s'affiche sur la page Équipe : <strong>Or</strong> = description détaillée + lien de vérification d'identité. <strong>Argent</strong> = mini description. <strong>Bronze</strong> = nom, poste et contact uniquement (pas de biographie affichée).
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Email de contact</label>
                <input type="email" name="contact_email" class="form-control" placeholder="prenom.nom@hitas.org" value="<?= e($member['contact_email'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Numéro WhatsApp</label>
                <input type="text" name="contact_whatsapp" class="form-control" placeholder="+237 6XX XXX XXX" value="<?= e($member['contact_whatsapp'] ?? '') ?>">
            </div>
        </div>
        <div class="form-text mb-3" style="margin-top:-0.75rem;">Remplissez au moins l'un des deux — affiché juste après la photo et le poste, pour toutes les catégories (Or, Argent, Bronze).</div>

        <div class="mb-3" id="bioField">
            <label class="form-label">Biographie</label>
            <textarea name="bio" class="form-control" rows="4"><?= e($member['bio']) ?></textarea>
            <div class="form-text">Pour la catégorie Or : description complète. Pour Argent : gardez-la courte (2-3 phrases), elle s'affiche en mini description.</div>
        </div>

        <div class="mb-3" id="verifyField">
            <label class="form-label">Lien de vérification d'identité <span class="text-muted fw-normal">(catégorie Or uniquement)</span></label>
            <input type="url" name="verify_link" class="form-control" placeholder="https://www.linkedin.com/in/..." value="<?= e($member['verify_link'] ?? '') ?>">
            <div class="form-text">Ex : profil LinkedIn officiel, ou toute page permettant de confirmer l'identité réelle de la personne. Affiché comme « Profil vérifié » sous sa description sur la page publique.</div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Ordre d'affichage</label>
                <input type="number" name="order_index" class="form-control" value="<?= (int)$member['order_index'] ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Statut</label>
                <select name="is_published" class="form-select">
                    <option value="1" <?= $member['is_published'] ? 'selected' : '' ?>>Publié</option>
                    <option value="0" <?= !$member['is_published'] ? 'selected' : '' ?>>Masqué</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Photo</label>
            <?php if (!empty($member['photo'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>team/<?= e($member['photo']) ?>" class="thumb-preview" style="width:100px;height:100px;"></div>
            <?php endif; ?>
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/team/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<script>
    // La biographie ne s'affiche publiquement que pour Or et Argent, et le
    // lien de vérification que pour Or — masqués ici pour éviter de remplir
    // des champs qui ne serviraient à rien pour Bronze.
    (function () {
        var tierSelect = document.getElementById('tierSelect');
        var bioField = document.getElementById('bioField');
        var verifyField = document.getElementById('verifyField');

        function updateFields() {
            var tier = tierSelect.value;
            bioField.style.display = (tier === 'bronze') ? 'none' : '';
            verifyField.style.display = (tier === 'gold') ? '' : 'none';
        }

        tierSelect.addEventListener('change', updateFields);
        updateFields();
    })();
</script>

<?php include __DIR__ . '/../footer.php'; ?>
