<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$slide = null;
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $slide = $stmt->fetch();
}

$pageTitle = $slide ? 'Modifier la slide' : 'Ajouter une slide';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 760px;">
    <form action="<?= BASE_URL ?>/admin/hero_slides/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($slide): ?><input type="hidden" name="id" value="<?= (int)$slide['id'] ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Légende interne (optionnelle, non affichée sur le site — pour vous repérer)</label>
            <input type="text" name="caption" class="form-control" value="<?= e($slide['caption'] ?? '') ?>">
        </div>

        <hr>
        <h6>Contenu affiché sur cette slide (facultatif)</h6>
        <div class="form-text mb-3">Si ces champs sont remplis, ils remplacent le titre/l'accroche du bloc « Accueil » pendant que cette slide est active. Laissez vide pour garder le texte par défaut.</div>

        <div class="mb-3">
            <label class="form-label">Titre</label>
            <input type="text" name="title" class="form-control" value="<?= e($slide['title'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Sous-titre</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= e($slide['subtitle'] ?? '') ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Texte du bouton</label>
                <input type="text" name="button_text" class="form-control" value="<?= e($slide['button_text'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lien du bouton</label>
                <input type="text" name="button_link" class="form-control" value="<?= e($slide['button_link'] ?? '') ?>">
            </div>
        </div>

        <hr>
        <h6>Durée d'affichage</h6>
        <div class="mb-3">
            <label class="form-label">Durée avant de passer à la slide suivante (en secondes)</label>
            <input type="number" step="0.5" min="1" name="duration_seconds" class="form-control" style="max-width:200px;" value="<?= e((string)(($slide['duration_ms'] ?? 6000) / 1000)) ?>">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="is_published" id="isPublished" class="form-check-input" <?= (!$slide || $slide['is_published']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isPublished">Visible sur le site</label>
        </div>

        <hr>
        <h6>Média de fond</h6>
        <div class="form-text mb-2">Une image ou une vidéo courte (silencieuse, en boucle). Si une vidéo est fournie, elle est utilisée à la place de l'image.</div>

        <div class="mb-3">
            <label class="form-label">Image <?= $slide ? '(laissez vide pour garder l\'actuelle)' : '' ?></label>
            <?php if (!empty($slide['image'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>hero/<?= e($slide['image']) ?>" style="max-height:120px; border-radius:8px;"></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Format paysage recommandé (ex. 1600×900), pour un rendu net derrière le texte d'accueil.</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Vidéo <?= !empty($slide['video']) ? '(laissez vide pour garder l\'actuelle)' : '(remplace l\'image si fournie)' ?></label>
            <?php if (!empty($slide['video'])): ?>
                <div class="mb-2"><video src="<?= UPLOAD_URL ?>hero/<?= e($slide['video']) ?>" style="max-height:120px; border-radius:8px;" muted></video></div>
            <?php endif; ?>
            <input type="file" name="video" class="form-control" accept=".mp4,.webm">
            <div class="form-text">Formats acceptés : mp4, webm. Fichier court et léger recommandé (quelques secondes, sans son).</div>
        </div>

        <?php if (!$slide): ?>
            <div class="alert alert-info small">Fournissez au moins une image ou une vidéo pour créer la slide.</div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary"><?= $slide ? 'Enregistrer' : 'Ajouter' ?></button>
        <a href="<?= BASE_URL ?>/admin/hero_slides/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
