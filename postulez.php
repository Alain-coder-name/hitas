<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$message = '';
$errors = [];

// Destinataires de la candidature :
// - Email : envoyé automatiquement, en arrière-plan, dès la soumission du
//   formulaire — à TOUTES les adresses configurées dans Réglages > Contact
//   (autant qu'il y en a, séparées par « / »).
// - WhatsApp : geste manuel du visiteur — un onglet s'ouvre avec le message
//   déjà écrit, il ne reste qu'à cliquer sur Envoyer dans WhatsApp.
// Calculés dès maintenant (pas seulement après envoi) pour que le bouton
// puisse ouvrir WhatsApp au moment même du clic.
$whatsappNumber = getSetting('applications_whatsapp', '655555957');
$whatsappBaseUrl = $whatsappNumber !== '' ? getWhatsAppUrl($whatsappNumber) : null;
$applicationsEmail = getSetting('applications_email') ?: getSetting('contact_email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Longueurs plafonnées : protège la base et la boîte mail contre les envois géants.
    $fullName = mb_substr(trim($_POST['full_name'] ?? ''), 0, 120);
    $contact = mb_substr(trim($_POST['contact'] ?? ''), 0, 60);
    $email = mb_substr(trim($_POST['email'] ?? ''), 0, 150);
    $educationLevel = trim($_POST['education_level'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $interest = trim($_POST['interest'] ?? '');
    $concerns = mb_substr(trim($_POST['concerns'] ?? ''), 0, 3000);
    $questions = mb_substr(trim($_POST['questions'] ?? ''), 0, 3000);

    // Anti-spam : piège à robots, jeton signé, délai minimal, 6 envois/heure/IP.
    $silentBot = false;
    $guardError = publicFormCheck('postulez', 6, $silentBot);
    if ($guardError !== null) {
        $errors[] = $guardError;
    } elseif ($silentBot) {
        // Robot : on fait comme si tout s'était bien passé, sans rien enregistrer ni envoyer.
        $message = 'Merci ! Votre demande a bien été reçue. Nous vous contacterons bientôt.';
        $fullName = $contact = $email = $educationLevel = $status = $interest = $concerns = $questions = '';
        goto formDone;
    }

    // Les listes déroulantes n'acceptent que leurs propres valeurs.
    if (!in_array($educationLevel, ['', 'Lycée', 'Licence', 'Autre'], true)) {
        $educationLevel = '';
    }
    if (!in_array($status, ['', 'Parent', 'Étudiant'], true)) {
        $status = '';
    }
    $allowedInterests = ['Admission à l\'institution', 'Admission à l\'université', 'Académique', 'Orientation professionnelle', 'Étudier à l\'étranger', 'Développement des compétences', 'Autre'];
    if (!in_array($interest, $allowedInterests, true)) {
        $interest = '';
    }

    if ($fullName === '') {
        $errors[] = 'Le nom et prénom sont obligatoires.';
    }
    if ($contact === '') {
        $errors[] = 'Le contact est obligatoire.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Une adresse e-mail valide est obligatoire.';
    }
    if ($status === '') {
        $errors[] = 'Le statut est obligatoire.';
    }
    if ($interest === '') {
        $errors[] = 'Le programme ou la filière d’intérêt est obligatoire.';
    }

    if (empty($errors)) {
        $subject = 'Demande - ' . $interest;
        $messageBody = "Contact: $contact\n";
        $messageBody .= "Niveau d'éducation: $educationLevel\n";
        $messageBody .= "Statut: $status\n";
        $messageBody .= "Programme / études d'intérêt: $interest\n\n";
        $messageBody .= "Préoccupations:\n$concerns\n\n";
        $messageBody .= "Questions:\n$questions";

        $stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?,?,?,?,?)');
        $stmt->execute([$fullName, $email, $contact, $subject, $messageBody]);

        // Envoi de la demande par email aux adresses configurées dans
        // Réglages > Contact (email du campus actif, sinon email général).
        $destinationEmails = parseContactEmails($applicationsEmail);
        if ($destinationEmails) {
            // oneLine() : aucun retour à la ligne possible dans le sujet / les en-têtes
            // (sinon injection d'en-têtes e-mail : copies cachées, spam relayé…).
            $emailSubject = 'Nouvelle demande HITAS - ' . oneLine($fullName, 100);
            $emailBody = "Nouvelle demande reçue sur le site HITAS :\n\n";
            $emailBody .= "Nom et prénom : $fullName\n";
            $emailBody .= "Contact : $contact\n";
            $emailBody .= "E-mail : $email\n";
            $emailBody .= "Statut : $status\n";
            $emailBody .= "Niveau d'éducation : $educationLevel\n";
            $emailBody .= "Programme / études d'intérêt : $interest\n\n";
            $emailBody .= "Préoccupations :\n" . ($concerns !== '' ? $concerns : '—') . "\n\n";
            $emailBody .= "Questions :\n" . ($questions !== '' ? $questions : '—') . "\n";

            // Domaine d'expédition tiré de l'adresse de contact configurée (jamais de l'en-tête
            // Host fourni par le visiteur, qui peut être falsifié).
            $senderDomain = 'hitas.org';
            $firstDest = $destinationEmails[0] ?? '';
            if (strpos($firstDest, '@') !== false && preg_match('/^[a-z0-9.\-]+$/i', substr(strrchr($firstDest, '@'), 1))) {
                $senderDomain = strtolower(substr(strrchr($firstDest, '@'), 1));
            }
            $emailHeaders = "From: HITAS Site <no-reply@" . $senderDomain . ">\r\n";
            $emailHeaders .= "Reply-To: " . oneLine($email, 150) . "\r\n";
            // mail() nécessite un serveur de messagerie configuré (SMTP/sendmail) ;
            // en local sous WAMP, cela peut ne rien envoyer sans configuration
            // supplémentaire (ex: php.ini -> sendmail_path, ou un relais SMTP).
            @mail(implode(',', $destinationEmails), $emailSubject, $emailBody, $emailHeaders);
        }

        // Lien WhatsApp pré-rempli, affiché en confirmation (l'ouverture au
        // clic est gérée côté formulaire, voir plus bas dans la page).
        $whatsappShareUrl = null;
        if ($whatsappBaseUrl) {
            $waText = "Nouvelle demande HITAS\n";
            $waText .= "Nom : $fullName\nContact : $contact\nProgramme : $interest";
            $whatsappShareUrl = $whatsappBaseUrl . '?text=' . rawurlencode($waText);
        }

        $message = 'Merci ! Votre demande a bien été reçue. Nous vous contacterons bientôt.';
        $fullName = $contact = $email = $educationLevel = $status = $interest = $concerns = $questions = '';
    }
}
formDone:

$pageTitle = 'Demande chez HITAS';
$pageDescription = 'Déposez votre demande auprès de HITAS en quelques minutes : vos informations, votre profil et votre projet d\'études.';
$currentPage = 'postulez';
$pageBackgroundKey = 'postulez';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color: #fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Postulez</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow text-white"><?= e(getSetting('postulez_hero_eyebrow', "Formulaire de demande")) ?></span>
                <h1 class="display-font mt-3"><?= e(getSetting('postulez_hero_title', 'Demande chez HITAS')) ?></h1>
                <p class="lead text-white-75"><?= e(getSetting('postulez_hero_lead', 'Veuillez compléter les informations ci-dessous pour que notre équipe vous accompagne dans votre projet d’études.')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="application-page py-5 reveal">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success js-toast-alert d-flex align-items-center gap-3">
                <i class="bi bi-check-circle-fill fs-3"></i>
                <div>
                    <strong>C'est envoyé !</strong><br>
                    <?= e($message) ?>
                    <?php if (!empty($whatsappShareUrl)): ?>
                        <br><span class="small d-inline-block mt-2"><i class="bi bi-whatsapp"></i> Une fenêtre WhatsApp s'est ouverte avec votre demande — il ne reste qu'à appuyer sur Envoyer là-bas.</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger js-toast-alert"><ul class="mb-0">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>

        <div class="application-card shadow-sm">
            <div class="application-header">
                <div>
                    <h2 class="mb-2">Formulaire de demande</h2>
                    <p class="text-muted">Trois étapes rapides : vos informations, votre profil, votre message. Notre équipe revient vers vous rapidement.</p>
                </div>
                <div class="application-badge">✦</div>
            </div>

            <div class="wizard-progress" id="wizardProgress"><div class="wizard-progress-bar" id="wizardProgressBar"></div></div>

            <form action="<?= BASE_URL ?>/postulez.php" method="post" class="application-form" id="applicationForm" <?= $whatsappBaseUrl ? 'data-whatsapp-base="' . e($whatsappBaseUrl) . '"' : '' ?>>
                <?= publicFormFields('postulez') ?>
                <div class="form-step">
                    <div class="form-step-heading">
                        <span class="form-step-num">1</span>
                        <div>
                            <h3 class="h6 mb-0">Vos informations</h3>
                            <p class="small text-muted mb-0">Pour que nous puissions vous recontacter.</p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Nom et prénom *</label>
                            <input type="text" name="full_name" maxlength="120" class="form-control" value="<?= e($fullName ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact (téléphone / WhatsApp) *</label>
                            <input type="text" name="contact" maxlength="60" class="form-control" value="<?= e($contact ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" maxlength="150" class="form-control" value="<?= e($email ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut *</label>
                            <select name="status" class="form-select" required>
                                <option value="">Sélectionnez</option>
                                <option value="Parent" <?= isset($status) && $status === 'Parent' ? 'selected' : '' ?>>Parent</option>
                                <option value="Étudiant" <?= isset($status) && $status === 'Étudiant' ? 'selected' : '' ?>>Étudiant</option>
                            </select>
                        </div>
                    </div>
                    <div class="wizard-nav">
                        <span></span>
                        <button type="button" class="btn btn-hitas-primary wizard-next">Suivant <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                <div class="form-step">
                    <div class="form-step-heading">
                        <span class="form-step-num">2</span>
                        <div>
                            <h3 class="h6 mb-0">Votre profil</h3>
                            <p class="small text-muted mb-0">Pour orienter votre demande vers la bonne filière.</p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Niveau d'éducation</label>
                            <select name="education_level" class="form-select">
                                <option value="">Sélectionnez</option>
                                <option value="Lycée" <?= isset($educationLevel) && $educationLevel === 'Lycée' ? 'selected' : '' ?>>Lycée</option>
                                <option value="Licence" <?= isset($educationLevel) && $educationLevel === 'Licence' ? 'selected' : '' ?>>Licence</option>
                                <option value="Autre" <?= isset($educationLevel) && $educationLevel === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Filière / études qui vous intéressent *</label>
                            <select name="interest" class="form-select" required>
                                <option value="">Sélectionnez</option>
                                <option value="Admission à l'institution" <?= isset($interest) && ($interest === 'Admission à l\'institution' || $interest === 'Admission à l\'université') ? 'selected' : '' ?>>Admission à l'institution</option>
                                <option value="Académique" <?= isset($interest) && ($interest === 'Académique' || $interest === 'Orientation professionnelle') ? 'selected' : '' ?>>Académique</option>
                                <option value="Étudier à l'étranger" <?= isset($interest) && $interest === 'Étudier à l\'étranger' ? 'selected' : '' ?>>Étudier à l'étranger</option>
                                <option value="Développement des compétences" <?= isset($interest) && $interest === 'Développement des compétences' ? 'selected' : '' ?>>Développement des compétences</option>
                                <option value="Autre" <?= isset($interest) && $interest === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                    </div>
                    <div class="wizard-nav">
                        <button type="button" class="btn btn-outline-secondary wizard-prev"><i class="bi bi-arrow-left"></i> Précédent</button>
                        <button type="button" class="btn btn-hitas-primary wizard-next">Suivant <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                <div class="form-step form-step--last">
                    <div class="form-step-heading">
                        <span class="form-step-num">3</span>
                        <div>
                            <h3 class="h6 mb-0">Votre message</h3>
                            <p class="small text-muted mb-0">Facultatif — dites-nous en plus si vous le souhaitez.</p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Préoccupations</label>
                            <textarea name="concerns" maxlength="3000" class="form-control" rows="4"><?= e($concerns ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Questions</label>
                            <textarea name="questions" maxlength="3000" class="form-control" rows="4"><?= e($questions ?? '') ?></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-between flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary wizard-prev"><i class="bi bi-arrow-left"></i> Précédent</button>
                            <button type="submit" class="btn btn-hitas-primary btn-lg">Envoyer ma demande</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php if ($whatsappBaseUrl): ?>
<script>
    // Ouvre WhatsApp au moment même du clic sur "Envoyer ma demande"
    // (et non après le rechargement de la page, où les navigateurs
    // bloquent souvent les popups automatiques). Le formulaire continue
    // ensuite son envoi normal vers le serveur (email + enregistrement).
    (function () {
        var form = document.getElementById('applicationForm');
        if (!form) return;
        var waBase = form.getAttribute('data-whatsapp-base');
        if (!waBase) return;

        form.addEventListener('submit', function () {
            var fullName = form.querySelector('[name="full_name"]');
            var contact = form.querySelector('[name="contact"]');
            var interest = form.querySelector('[name="interest"]');

            var text = 'Nouvelle demande HITAS\n';
            text += 'Nom : ' + (fullName ? fullName.value : '') + '\n';
            text += 'Contact : ' + (contact ? contact.value : '') + '\n';
            text += 'Programme : ' + (interest ? interest.value : '');

            // Appelé en tout premier, en réponse directe au clic, pour que
            // le navigateur autorise l'ouverture du nouvel onglet.
            window.open(waBase + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
        });
    })();
</script>
<?php endif; ?>

<script>
    // Transforme le formulaire "Postulez" en assistant pas-à-pas (une étape
    // visible à la fois) avec une barre de progression. Si ce script ne
    // s'exécute pas (JS désactivé), le formulaire reste utilisable tel
    // quel : les 3 étapes s'affichent toutes à la suite (voir CSS).
    (function () {
        var form = document.getElementById('applicationForm');
        var progress = document.getElementById('wizardProgress');
        var progressBar = document.getElementById('wizardProgressBar');
        if (!form || !progress || !progressBar) return;

        var steps = Array.prototype.slice.call(form.querySelectorAll('.form-step'));
        if (steps.length < 2) return;

        var current = 0;

        function showStep(index) {
            current = index;
            steps.forEach(function (step, i) {
                step.classList.toggle('is-active', i === index);
            });
            progressBar.style.width = (((index + 1) / steps.length) * 100) + '%';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function currentStepIsValid() {
            var fields = steps[current].querySelectorAll('input, select, textarea');
            for (var i = 0; i < fields.length; i++) {
                if (!fields[i].reportValidity()) {
                    return false;
                }
            }
            return true;
        }

        form.querySelectorAll('.wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!currentStepIsValid()) return;
                showStep(current + 1);
            });
        });

        form.querySelectorAll('.wizard-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showStep(current - 1);
            });
        });

        form.classList.add('is-wizard');
        progress.classList.add('is-visible');
        showStep(0);
    })();
</script>

<?php include __DIR__ . '/includes/site_footer.php';
