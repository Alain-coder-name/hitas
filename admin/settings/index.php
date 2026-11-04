<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = array_column($rows, 'setting_value', 'setting_key');

$pageTitle = 'Paramètres généraux';
include __DIR__ . '/../header.php';
?>

<div class="card p-4" style="max-width: 700px;">
    <form action="<?= BASE_URL ?>/admin/settings/save.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">Nom du site</label>
            <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Logo</label>
            <div class="mb-2 p-2 border rounded" style="display:inline-block; background:#f7f5f0;"><img src="<?= BASE_URL ?>/assets/img/logo-hitas-officiel.jpg" style="height:70px; max-width:320px; object-fit:contain;"></div>
            <div class="form-text">Le logo officiel HITAS est fixe et ne peut pas être modifié depuis l'admin.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Favicon (icône d'onglet)</label>
            <?php if (!empty($settings['favicon'])): ?>
                <div class="mb-2"><img src="<?= UPLOAD_URL ?>settings/<?= e($settings['favicon']) ?>" style="width:32px; height:32px; object-fit:contain;"></div>
            <?php endif; ?>
            <input type="file" name="favicon" class="form-control" accept=".png,.ico">
            <div class="form-text">Format carré recommandé (PNG 512×512 ou .ico). Les fichiers SVG ne sont pas acceptés (sécurité).</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Slogan (sous le nom, dans le menu)</label>
            <input type="text" name="site_slogan" class="form-control" value="<?= e($settings['site_slogan'] ?? 'Technology & Applied Sciences') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Couleur principale</label>
            <input type="color" name="primary_color" class="form-control form-control-color" value="<?= e($settings['primary_color'] ?? '#002147') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Couleur accent (rouge)</label>
            <input type="color" name="accent_color" class="form-control form-control-color" value="<?= e($settings['accent_color'] ?? '#9c7c3c') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Couleur fond clair (ivoire)</label>
            <input type="color" name="ivory_color" class="form-control form-control-color" value="<?= e($settings['ivory_color'] ?? '#f7f5f0') ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Email(s) de contact</label>
                <input type="text" name="contact_email" class="form-control" value="<?= e($settings['contact_email'] ?? '') ?>">
                <div class="form-text">Plusieurs emails possibles, séparés par un « / » (ex: contact@hitas.org / registrar@hitas.org).</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Téléphone(s) WhatsApp</label>
                <input type="text" name="contact_phone" class="form-control" value="<?= e($settings['contact_phone'] ?? '') ?>">
                <div class="form-text">Plusieurs numéros possibles, séparés par un « / ». Affichés dans le footer avec un lien WhatsApp.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Téléphone d'appel classique</label>
                <input type="text" name="call_phone" class="form-control" value="<?= e($settings['call_phone'] ?? '') ?>" placeholder="Ex: +237 6XX XXX XXX">
                <div class="form-text">Numéro d'appel affiché dans le footer, indépendant du/des numéro(s) WhatsApp ci-dessus. Cliquable ("appeler") sur mobile.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Numéros WhatsApp du bouton flottant (Accueil)</label>
                <input type="text" name="floating_whatsapp_numbers" class="form-control" value="<?= e($settings['floating_whatsapp_numbers'] ?? '') ?>" placeholder="Ex: 658595959 / 655555957">
                <div class="form-text">Le petit bouton d'appel flottant en bas à droite de l'accueil. Un ou plusieurs numéros, séparés par « / ».</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">E-mail(s) de réception des candidatures</label>
                <input type="text" name="applications_email" class="form-control" value="<?= e($settings['applications_email'] ?? '') ?>" placeholder="admission@hitas.org">
                <div class="form-text">Les demandes envoyées depuis la page « Postulez » sont adressées à ces e-mails (plusieurs possibles, séparés par « / »). Laissez vide pour utiliser les e-mails de contact ci-dessus.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Numéro WhatsApp de réception des candidatures</label>
                <input type="text" name="applications_whatsapp" class="form-control" value="<?= e($settings['applications_whatsapp'] ?? '') ?>" placeholder="Ex: 658595959">
                <div class="form-text">Numéro qui s'ouvre dans WhatsApp quand le visiteur envoie sa demande depuis la page « Postulez ».</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Adresse</label>
                <input type="text" name="address" class="form-control" value="<?= e($settings['address'] ?? '') ?>">
                <div class="form-text">Plusieurs lignes possibles, séparées par un « / ».</div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Boîte postale</label>
                <input type="text" name="po_box" class="form-control" value="<?= e($settings['po_box'] ?? '') ?>" placeholder="Ex: B.P. 5696 Douala">
                <div class="form-text">Affichée dans le footer, sous l'adresse.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Recherche Google Maps <span class="text-muted fw-normal">(optionnel)</span></label>
                <input type="text" name="maps_query" class="form-control" value="<?= e($settings['maps_query'] ?? '') ?>" placeholder="Laissez vide pour utiliser l'adresse ci-dessus">
                <div class="form-text">Texte utilisé pour localiser HITAS sur Google Maps (page FAQ). Utile si l'adresse exacte n'est pas assez précise pour Maps — sinon, l'adresse suffit.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Facebook</label>
                <input type="url" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? '') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">LinkedIn</label>
                <input type="url" name="linkedin_url" class="form-control" value="<?= e($settings['linkedin_url'] ?? '') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Instagram</label>
                <input type="url" name="instagram_url" class="form-control" value="<?= e($settings['instagram_url'] ?? '') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">TikTok</label>
                <input type="url" name="tiktok_url" class="form-control" placeholder="https://www.tiktok.com/@hitas" value="<?= e($settings['tiktok_url'] ?? '') ?>">
            </div>
        </div>

        <hr>
        <h5>Visionneuse de photos et vidéos</h5>
        <p class="text-muted small">Quand un visiteur clique sur une photo ou une vidéo (galerie « Vie étudiante », photos d'une actualité), elles s'ouvrent en grand avec des flèches pour avancer et reculer, et peuvent défiler toutes seules.</p>
        <div class="mb-4">
            <label class="form-label">Temps de déroulement automatique (secondes par photo)</label>
            <input type="number" name="lightbox_delay" class="form-control" style="max-width:160px;" min="2" max="60" value="<?= e($settings['lightbox_delay'] ?? '4') ?>">
            <div class="form-text">Entre 2 et 60 secondes. Le visiteur peut mettre le défilement en pause à tout moment. Une vidéo n'est jamais coupée : le défilement attend la fin de la vidéo.</div>
        </div>

        <hr>
        <h5>Chaîne WhatsApp</h5>
        <p class="text-muted small">Lien vers votre chaîne WhatsApp. Une fois renseigné, le site propose automatiquement aux visiteurs de la rejoindre (voir détails plus bas dans cette conversation).</p>
        <div class="mb-4">
            <label class="form-label">Lien de la chaîne WhatsApp</label>
            <input type="url" name="whatsapp_channel_url" class="form-control" placeholder="https://whatsapp.com/channel/xxxxxxxxxxxxx" value="<?= e($settings['whatsapp_channel_url'] ?? '') ?>">
        </div>

        <hr>
        <h5>Chaîne HITAS TV</h5>
        <p class="text-muted small">Lien vers votre chaîne HITAS TV (YouTube ou autre). Une fois renseigné, un bouton « Rejoindre notre chaîne HITAS TV » apparaît sur l'accueil, sous les bulles.</p>
        <div class="mb-4">
            <label class="form-label">Lien de la chaîne HITAS TV</label>
            <input type="url" name="hitas_tv_url" class="form-control" placeholder="https://youtube.com/@hitastv" value="<?= e($settings['hitas_tv_url'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
