<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['title' => '', 'excerpt' => '', 'content' => '', 'image' => '', 'video' => '', 'is_published' => 1, 'is_advertisement' => 0, 'external_link' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { die('Article introuvable.'); }
    $item = $found;

    $stmt2 = $pdo->prepare('SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt2->execute([$id]);
    $newsImages = $stmt2->fetchAll();
} else {
    $newsImages = [];
}

$pageTitle = $id ? 'Modifier un article' : 'Ajouter un article';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <form action="<?= BASE_URL ?>/admin/news/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Titre</label>
            <input type="text" name="title" class="form-control" value="<?= e($item['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Résumé (affiché dans les listes)</label>
            <textarea name="excerpt" class="form-control" rows="2"><?= e($item['excerpt']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Contenu complet</label>
            <textarea name="content" class="form-control" rows="8"><?= e($item['content']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="is_published" class="form-select">
                <option value="1" <?= $item['is_published'] ? 'selected' : '' ?>>Publié</option>
                <option value="0" <?= !$item['is_published'] ? 'selected' : '' ?>>Brouillon</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="is_advertisement" id="is_advertisement" class="form-select" onchange="toggleExternalLink()">
                <option value="0" <?= !$item['is_advertisement'] ? 'selected' : '' ?>>Actualité</option>
                <option value="1" <?= $item['is_advertisement'] ? 'selected' : '' ?>>Publicité</option>
            </select>
        </div>

        <div class="mb-3" id="external_link_wrapper" style="<?= $item['is_advertisement'] ? '' : 'display:none;' ?>">
            <label class="form-label">Lien externe de destination (obligatoire pour une publicité)</label>
            <input type="url" name="external_link" class="form-control" placeholder="https://www.exemple.com/offre" value="<?= e($item['external_link'] ?? '') ?>">
            <div class="form-text">Quand un visiteur clique sur cette publicité (sur la page d'accueil ou la page Actualités), il sera redirigé vers ce lien externe, dans un nouvel onglet, au lieu d'ouvrir un article interne.</div>
        </div>

        <script>
        function toggleExternalLink() {
            const isAd = document.getElementById('is_advertisement').value === '1';
            document.getElementById('external_link_wrapper').style.display = isAd ? '' : 'none';
        }
        </script>

        <div class="mb-4">
            <label class="form-label">Photos de l'actualité</label>
            <div class="form-text mb-2">Plusieurs photos possibles. Choisissez laquelle est la « couverture » : c'est elle qui apparaît sur la page d'accueil et dans les listes (utilisée aussi si aucune vidéo n'est fournie ci-dessous).</div>
            <?php if ($newsImages): ?>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <?php foreach ($newsImages as $img): ?>
                        <div class="border rounded p-2 text-center" style="width:130px;">
                            <img src="<?= UPLOAD_URL ?>news/<?= e($img['image']) ?>" class="thumb-preview mb-2" style="width:110px;height:110px;object-fit:cover;border-radius:6px;">
                            <div class="form-check d-flex justify-content-center gap-1 mb-1">
                                <input class="form-check-input" type="radio" name="cover_id" id="cover_<?= (int)$img['id'] ?>" value="<?= (int)$img['id'] ?>" <?= $img['is_cover'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="cover_<?= (int)$img['id'] ?>">Couverture</label>
                            </div>
                            <div class="form-check d-flex justify-content-center gap-1">
                                <input class="form-check-input" type="checkbox" name="delete_ids[]" id="del_<?= (int)$img['id'] ?>" value="<?= (int)$img['id'] ?>">
                                <label class="form-check-label small text-danger" for="del_<?= (int)$img['id'] ?>">Supprimer</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" name="new_images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
            <div class="form-text">Ajoutez une ou plusieurs photos (sélection multiple possible). La toute première photo ajoutée à un article devient automatiquement sa couverture.</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Vidéo de couverture <span class="text-muted fw-normal">(optionnelle — prend le pas sur l'image si fournie)</span></label>
            <?php if (!empty($item['video'])): ?>
                <div class="mb-2"><video src="<?= UPLOAD_URL ?>news/<?= e($item['video']) ?>" style="max-height:120px; border-radius:8px;" muted controls></video></div>
                <div class="form-text mb-2">Laissez le champ ci-dessous vide pour garder la vidéo actuelle.</div>
            <?php endif; ?>
            <input type="file" name="video" class="form-control" accept=".mp4,.webm">
            <div class="form-text">Formats acceptés : MP4 ou WebM. Utile pour une actualité ou une publicité animée — s'affiche notamment dans les bulles de la page d'accueil.</div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= BASE_URL ?>/admin/news/list.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
