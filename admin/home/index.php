<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = array_column($rows, 'setting_value', 'setting_key');

$pageTitle = 'Contenu page d\'accueil';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 900px;">
    <form action="<?= BASE_URL ?>/admin/home/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <h5>Hero</h5>
        <div class="mb-3">
            <label class="form-label">Eyebrow (petit texte)</label>
            <input type="text" name="home_hero_eyebrow" class="form-control" value="<?= e($settings['home_hero_eyebrow'] ?? 'Hanseatic Institute of Technology and Applied Sciences') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Titre principal</label>
            <input type="text" name="home_hero_title" class="form-control" value="<?= e($settings['home_hero_title'] ?? 'Une à deux années pour préparer votre départ vers une institution partenaire, à l\'étranger.') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Accroche (lead)</label>
            <textarea name="home_hero_lead" class="form-control" rows="3"><?= e($settings['home_hero_lead'] ?? 'HITAS forme les bacheliers dans un cadre exigeant, puis les accompagne vers l\'une de nos institutions partenaires selon leur filière — la passerelle entre votre diplôme et votre avenir académique international.') ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Texte bouton principal</label>
                <input type="text" name="home_hero_cta_text" class="form-control" value="<?= e($settings['home_hero_cta_text'] ?? 'Découvrir les programmes de nos institutions partenaires') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lien bouton principal</label>
                <input type="text" name="home_hero_cta_link" class="form-control" value="<?= e($settings['home_hero_cta_link'] ?? '/programmes.php') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Photos de fond de la section d'accueil (juste sous le menu)</label>
            <div class="form-text mb-2">Le fond de l'accueil est maintenant un diaporama de plusieurs photos qui défilent automatiquement, géré depuis une page dédiée.</div>
            <a href="<?= BASE_URL ?>/admin/hero_slides/list.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-images"></i> Gérer le diaporama accueil</a>
        </div>

        <hr>
        <h5>Piliers (3)</h5>
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="mb-3">
                <label class="form-label">Pilier <?= $i ?> - Titre</label>
                <input type="text" name="home_pillar_<?= $i ?>_title" class="form-control" value="<?= e($settings["home_pillar_{$i}_title"] ?? ['Exigence académique','Accompagnement personnel','Ouverture internationale'][$i-1]) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Pilier <?= $i ?> - Texte</label>
                <textarea name="home_pillar_<?= $i ?>_text" class="form-control" rows="3"><?= e($settings["home_pillar_{$i}_text"] ?? '') ?></textarea>
            </div>
        <?php endfor; ?>

        <hr>
        <h5>CTA final</h5>
        <div class="mb-3">
            <label class="form-label">Titre CTA</label>
            <input type="text" name="home_cta_title" class="form-control" value="<?= e($settings['home_cta_title'] ?? 'Prêt à commencer votre passerelle vers l\'international ?') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Sous-texte CTA</label>
            <input type="text" name="home_cta_subtitle" class="form-control" value="<?= e($settings['home_cta_subtitle'] ?? 'Consultez les conditions d\'admission ou contactez notre équipe pour être guidé dans votre choix de programme.') ?>">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Texte bouton 1</label>
                <input type="text" name="home_cta_btn1_text" class="form-control" value="<?= e($settings['home_cta_btn1_text'] ?? 'Nous contacter') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lien bouton 1</label>
                <input type="text" name="home_cta_btn1_link" class="form-control" value="<?= e($settings['home_cta_btn1_link'] ?? '/postulez.php') ?>">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
