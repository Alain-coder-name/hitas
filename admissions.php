<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
$stmt->execute(['admissions']);
$page = $stmt->fetch();

$pageTitle = ($page['title'] ?? 'Admissions') . ' — HITAS';
$pageDescription = 'Conditions d\'admission, dossier de candidature et calendrier pour rejoindre HITAS.';
applySeoOverrides($page);
$currentPage = 'admissions';
$pageBackgroundKey = 'admissions';
include __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #001328 0%, #002147 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admissions</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow text-white">Admissions</span>
                <h1 class="display-font mt-3">Rejoignez HITAS pour une préparation internationale</h1>
                <p class="lead text-white-75">Retrouvez les conditions d’admission, le calendrier et les étapes de candidature.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="card shadow-sm p-4">
            <?php if ($page && $page['content']): ?>
                <div class="page-content">
                    <?= sanitizeRichHtml($page['content']) ?>
                </div>
            <?php else: ?>
                <div class="row g-4 align-items-center">
                    <div class="col-lg-5">
                        <div class="admissions-visual-card">
                            <div class="admissions-visual-badge">Admissions</div>
                            <div class="admissions-visual-glyph"><i class="bi bi-mortarboard"></i></div>
                            <div class="admissions-visual-overlay"></div>
                            <div class="admissions-visual-copy">
                                <h3>Préparez votre dossier</h3>
                                <p>Rassemblez les pièces essentielles pour postuler à HITAS et maximiser vos chances.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <h2 class="mb-3">Dossier de candidature</h2>
                        <ul class="list-unstyled mb-4">
                            <li>• Une lettre de candidature manuscrite adressée au président de HITAS</li>
                            <li>• Une copie certifiée conforme de l'acte de naissance</li>
                            <li>• Une photocopie du certificat GCE A/Level ou du baccalauréat (ou du relevé de notes)</li>
                            <li>• Photocopies des bulletins scolaires de la Première et de la Terminale</li>
                            <li>• Deux (2) photos d'identité récentes (4×4)</li>
                            <li>• Des frais de traitement de dossier non remboursables de 20 000 FCFA sont exigés</li>
                            <li>• Une enveloppe A4 contenant l'adresse postale du demandeur</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>AVIS IMPORTANT</strong><br>
                            Les candidats sont invités à s'assurer que tous les documents requis sont complets avant de soumettre leur candidature afin d'éviter tout retard de traitement.
                        </div>
                        <p>
                            Tous les dossiers de candidature complets doivent être soumis au secrétariat du président de HITAS ou envoyés à l'avance sous forme de copies numérisées ou au format PDF à l'adresse électronique <a href="mailto:admission@hitas.org">admission@hitas.org</a>, en copie à <a href="mailto:contact@hitas.org">contact@hitas.org</a> et <a href="mailto:registrar@hitas.org">registrar@hitas.org</a>.
                        </p>
                        <p class="fw-semibold">Les dossiers incomplets ne seront pas traités. L'admission n'est confirmée qu'après la signature d'une convention tripartite entre HITAS, l'étudiant et son parent ou tuteur.</p>
                        <p>Dès son inscription, l'étudiant recevra un ordinateur portable, une tenue habillée (blazer) et un tissu de marque HITAS.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="admission-dates mt-5 p-4 rounded-4 border">
                <h2 class="h4 mb-3">Dates importantes</h2>
                <p>Veuillez prendre note du calendrier d'admission suivant.</p>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <div class="date-card p-3 rounded-4 bg-light">
                            <h3 class="h5 mb-2">Date de début du semestre</h3>
                            <p class="mb-0"><strong>Octobre 2026</strong></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="date-card p-3 rounded-4 bg-light">
                            <h3 class="h5 mb-2">Date limite d'admission finale</h3>
                            <p class="mb-0"><strong>31 octobre 2026</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-primary btn-lg btn-postulez">Postuler maintenant</a>
            <a href="<?= BASE_URL ?>/faq.php" class="btn btn-outline-secondary btn-lg">Des questions ? Consultez la FAQ</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site_footer.php';
