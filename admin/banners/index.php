<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = array_column($rows, 'setting_value', 'setting_key');

// Une entrée par page : clé de préfixe, libellé, et valeurs par défaut
// (identiques au texte actuellement affiché en dur sur chaque page publique).
$pages = [
    'programmes' => [
        'label' => 'Programmes',
        'eyebrow' => 'Programmes',
        'title' => 'Programmes à HITAS',
        'lead' => "Un parcours académique et professionnel pensé pour ouvrir les portes des institutions internationales, des carrières et de l'entrepreneuriat.",
    ],
    'partenaires' => [
        'label' => 'Partenaires',
        'eyebrow' => 'Partenaires',
        'title' => 'Nos institutions partenaires',
        'lead' => 'Découvrez les établissements qui accueillent nos diplômés et les destinations de nos programmes.',
    ],
    'equipe' => [
        'label' => 'Équipe',
        'eyebrow' => "L'équipe HITAS",
        'title' => 'Les personnes qui font HITAS',
        'lead' => "Une équipe engagée auprès des étudiants, de l'admission jusqu'à l'obtention du diplôme.",
    ],
    'actualites' => [
        'label' => 'Actualités',
        'eyebrow' => 'Actualités',
        'title' => 'Les dernières nouvelles de HITAS',
        'lead' => 'Restez informé des actualités, événements et opportunités pour nos étudiants et nos programmes.',
    ],
    'postulez' => [
        'label' => 'Postulez',
        'eyebrow' => "Formulaire d'inscription",
        'title' => 'Postulez avec HITAS',
        'lead' => 'Veuillez compléter les informations ci-dessous pour que notre équipe vous accompagne dans votre projet d’études.',
    ],
];

$pageTitle = 'Bandeaux des pages';
include __DIR__ . '/../header.php';
?>

<p class="text-muted mb-4">Modifiez ici le texte du bandeau (titre, sous-titre et petit texte d'introduction) affiché en haut des pages Programmes, Partenaires, Équipe, Actualités et Postulez — de la même façon que pour l'accueil.</p>

<form action="<?= BASE_URL ?>/admin/banners/save.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <?php foreach ($pages as $key => $defaults): ?>
        <div class="card p-4 mb-4">
            <h5 class="mb-3"><?= e($defaults['label']) ?></h5>
            <div class="mb-3">
                <label class="form-label">Eyebrow (petit texte au-dessus du titre)</label>
                <input type="text" name="<?= e($key) ?>_hero_eyebrow" class="form-control" value="<?= e($settings["{$key}_hero_eyebrow"] ?? $defaults['eyebrow']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Titre du bandeau</label>
                <input type="text" name="<?= e($key) ?>_hero_title" class="form-control" value="<?= e($settings["{$key}_hero_title"] ?? $defaults['title']) ?>">
            </div>
            <div class="mb-0">
                <label class="form-label">Accroche (texte sous le titre)</label>
                <textarea name="<?= e($key) ?>_hero_lead" class="form-control" rows="2"><?= e($settings["{$key}_hero_lead"] ?? $defaults['lead']) ?></textarea>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary">Annuler</a>
</form>

<?php include __DIR__ . '/../footer.php'; ?>
