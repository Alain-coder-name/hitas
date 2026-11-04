<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$faqs = $pdo->query('SELECT * FROM faq WHERE is_published = 1 ORDER BY order_index ASC, id ASC')->fetchAll();

// Photos du diaporama "Conditions d'admission" — gérées depuis
// /admin/page_galleries. Si aucune photo n'est ajoutée, la carte
// décorative d'origine reste affichée (voir plus bas). Protégé : si la
// table n'existe pas encore (migration non exécutée), on ne fait pas
// planter toute la page — on retombe simplement sur la carte décorative.
try {
    $stmt = $pdo->prepare('SELECT image FROM page_galleries WHERE page_key = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute(['admission-conditions']);
    $admissionImages = array_column($stmt->fetchAll(), 'image');
} catch (PDOException $e) {
    $admissionImages = [];
}

$currentPage = 'faq';
$pageTitle = 'Questions fréquentes — HITAS';
$pageDescription = "Les réponses aux questions les plus fréquentes sur l'admission, les filières et le départ vers une institution partenaire avec HITAS.";
applySeoOverrides(getPageSeo('faq'));
$pageBackgroundKey = 'faq';
require __DIR__ . '/includes/site_header.php';
?>

<section class="page-hero py-5" style="background: linear-gradient(160deg, #1B6FBF 0%, #2E86DE 100%); color:#fff;">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">FAQ</li>
            </ol>
        </nav>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="eyebrow text-white">Questions fréquentes</span>
                <h1 class="display-font mt-3">Une question sur HITAS ?</h1>
                <p class="lead text-white-75">Retrouvez ici les réponses aux questions les plus posées sur l'admission, les filières et le départ vers nos institutions partenaires.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section py-5 reveal">
    <div class="container">
        <?php if (empty($faqs)): ?>
            <div class="alert alert-warning text-center">Aucune question publiée pour le moment.</div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $i => $item): ?>
                            <?php $isAdmissionFaq = !isset($admissionFaqFound) && stripos($item['question'], 'admission') !== false; if ($isAdmissionFaq) { $admissionFaqFound = true; } ?>
                            <div class="accordion-item mb-3 border rounded-4 overflow-hidden"<?= $isAdmissionFaq ? ' id="faq-admission"' : '' ?>>
                                <h2 class="accordion-header" id="faqHeading<?= (int)$item['id'] ?>">
                                    <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= (int)$item['id'] ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faqCollapse<?= (int)$item['id'] ?>">
                                        <?= e($item['question']) ?>
                                    </button>
                                </h2>
                                <div id="faqCollapse<?= (int)$item['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" aria-labelledby="faqHeading<?= (int)$item['id'] ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        <?= nl2br(e($item['answer'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="cta-banner text-center mt-5">
            <h2 class="h3 mb-2">Vous ne trouvez pas la réponse à votre question ?</h2>
            <p class="mb-4">Consultez les conditions et le calendrier d'admission, ou déposez directement votre candidature.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="#admission-conditions" class="btn btn-light btn-lg">Voir les conditions d'admission</a>
                <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-primary btn-lg btn-postulez">Postuler maintenant</a>
            </div>
        </div>

        <!-- Conditions d'admission — anciennement une page séparée
             (admissions.php), désormais une section à part entière ici,
             puisqu'elle répond au même besoin que la FAQ. -->
        <div class="row justify-content-center mt-5" id="admission-conditions">
            <div class="col-lg-9">
                <div class="text-center mb-4">
                    <span class="section-eyebrow">Constituer son dossier</span>
                    <h2 class="section-title">Conditions d'admission</h2>
                </div>
                <div class="card shadow-sm p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-5">
                            <?php if ($admissionImages): ?>
                                <div class="admissions-visual-slideshow" data-duration="5000">
                                    <?php foreach ($admissionImages as $i => $img): ?>
                                        <div class="admissions-visual-slide<?= $i === 0 ? ' is-active' : '' ?>" style="background-image:url('<?= UPLOAD_URL ?>page_galleries/<?= e($img) ?>')"></div>
                                    <?php endforeach; ?>
                                    <?php if (count($admissionImages) > 1): ?>
                                        <div class="admissions-visual-dots">
                                            <?php foreach ($admissionImages as $i => $img): ?>
                                                <span class="admissions-visual-dot<?= $i === 0 ? ' is-active' : '' ?>"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="admissions-visual-card">
                                    <div class="admissions-visual-badge">Admissions</div>
                                    <div class="admissions-visual-glyph"><i class="bi bi-mortarboard"></i></div>
                                    <div class="admissions-visual-overlay"></div>
                                    <div class="admissions-visual-copy">
                                        <h3>Préparez votre dossier</h3>
                                        <p>Rassemblez les pièces essentielles pour postuler à HITAS et maximiser vos chances.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-7">
                            <h3 class="h5 mb-3">Dossier de candidature</h3>
                            <ul class="list-unstyled mb-4">
                                <li>• Une lettre de candidature manuscrite adressée au président de HITAS</li>
                                <li>• Une copie certifiée conforme de l'acte de naissance</li>
                                <li>• Une photocopie du certificat GCE A/Level ou du baccalauréat (ou du relevé de notes)</li>
                                <li>• Photocopies des bulletins scolaires de la Première et de la Terminale</li>
                                <li>• Deux (2) photos d'identité récentes (4×4)</li>
                                <li>• Des frais de traitement de dossier non remboursables de 20 000 FCFA sont exigés</li>
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
                            <p class="mb-0">Dès son inscription, l'étudiant recevra un ordinateur portable, une tenue habillée (blazer) et un tissu de marque HITAS.</p>
                        </div>
                    </div>

                    <div class="admission-dates mt-5 p-4 rounded-4 border">
                        <h3 class="h5 mb-3">Dates importantes</h3>
                        <p>Veuillez prendre note du calendrier d'admission suivant.</p>
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <div class="date-card p-3 rounded-4 bg-light">
                                    <h4 class="h6 mb-2">Date de début du semestre</h4>
                                    <p class="mb-0"><strong>Octobre 2026</strong></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="date-card p-3 rounded-4 bg-light">
                                    <h4 class="h6 mb-2">Date limite d'admission finale</h4>
                                    <p class="mb-0"><strong>31 octobre 2026</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="<?= BASE_URL ?>/postulez.php" class="btn btn-hitas-primary btn-lg btn-postulez">Postuler maintenant</a>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
