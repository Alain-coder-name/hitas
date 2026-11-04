<?php
require_once __DIR__ . '/includes/site_helpers.php';

$pdo = getDB();
$tierOrder = "FIELD(tier, 'gold', 'silver', 'bronze')";
$team = $pdo->query("SELECT * FROM team_members WHERE is_published = 1 ORDER BY $tierOrder, order_index ASC, id ASC")->fetchAll();

// Répartition par catégorie pour l'affichage en arbre : Or (direction),
// Argent (responsables), Bronze (employés). Gérées depuis /admin/team.
$tiers = ['gold' => [], 'silver' => [], 'bronze' => []];
foreach ($team as $member) {
    $t = in_array($member['tier'], ['gold', 'silver', 'bronze'], true) ? $member['tier'] : 'bronze';
    $tiers[$t][] = $member;
}

// L'équipe (bronze) est affichée par ordre alphabétique du prénom/nom,
// contrairement à Or/Argent qui gardent l'ordre défini depuis l'admin.
// Les civilités (M., Mme, Mlle, Dr, Pr...) éventuellement saisies au début
// du nom sont ignorées pour le tri, sinon "Dr. X" et "Mme Y" se
// classeraient par civilité au lieu du nom — seul l'affichage garde le
// nom complet tel que saisi.
function teamSortableName(string $fullName): string
{
    return preg_replace('/^(m|mme|mlle|dr|pr|prof)\.?\s+/iu', '', trim($fullName));
}
if (class_exists('Collator')) {
    $nameCollator = new Collator('fr_FR');
    usort($tiers['bronze'], fn($a, $b) => $nameCollator->compare(teamSortableName($a['full_name']), teamSortableName($b['full_name'])));
} else {
    usort($tiers['bronze'], fn($a, $b) => strcasecmp(teamSortableName($a['full_name']), teamSortableName($b['full_name'])));
}

function teamInitials(string $fullName): string
{
    $initials = '';
    foreach (explode(' ', trim($fullName)) as $part) {
        if ($part !== '') { $initials .= mb_strtoupper(mb_substr($part, 0, 1)); }
    }
    return mb_substr($initials, 0, 2);
}

// Affiche le contact (email ou WhatsApp, ou les deux) juste après la photo
// et le poste — visible pour Or, Argent et Bronze.
function renderTeamContact(array $member): void
{
    $email = trim($member['contact_email'] ?? '');
    $whatsapp = trim($member['contact_whatsapp'] ?? '');
    if ($email === '' && $whatsapp === '') {
        return;
    }
    echo '<div class="team-node-contact">';
    if ($email !== '') {
        echo '<a href="mailto:' . e($email) . '" class="team-node-contact-link"><i class="bi bi-envelope-fill"></i></a>';
    }
    if ($whatsapp !== '') {
        $waUrl = getWhatsAppUrl($whatsapp);
        if ($waUrl) {
            echo '<a href="' . e($waUrl) . '" target="_blank" rel="noopener noreferrer" class="team-node-contact-link team-node-contact-link--whatsapp"><i class="bi bi-whatsapp"></i></a>';
        }
    }
    echo '</div>';
}

// Bouton "En savoir plus" (Or et Argent uniquement) : ouvre une fenêtre
// affichant la description complète, plutôt que de l'afficher sur la carte.
function renderTeamMoreButton(array $member): void
{
    if (empty($member['bio']) && empty($member['verify_link'])) {
        return;
    }
    echo '<button type="button" class="team-node-more" data-bs-toggle="modal" data-bs-target="#teamModal' . (int)$member['id'] . '">En savoir plus <i class="bi bi-arrow-right"></i></button>';
}

// Fenêtre modale associée (rendue en dehors des cartes, une par membre
// Or/Argent, pour éviter tout conflit avec l'effet de survol des cartes).
function renderTeamModal(array $member): void
{
    if (empty($member['bio']) && empty($member['verify_link'])) {
        return;
    }
    ?>
    <div class="modal fade team-modal" id="teamModal<?= (int)$member['id'] ?>" tabindex="-1" aria-labelledby="teamModalLabel<?= (int)$member['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="teamModalLabel<?= (int)$member['id'] ?>"><?= e($member['full_name']) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($member['photo'])): ?>
                        <img src="<?= e(UPLOAD_URL . 'team/' . $member['photo']) ?>" alt="<?= e($member['full_name']) ?>" class="team-modal-photo" loading="lazy">
                    <?php else: ?>
                        <div class="team-modal-photo team-modal-photo--placeholder"><?= e(teamInitials($member['full_name'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($member['role'])): ?><p class="team-node-role mb-3"><?= e($member['role']) ?></p><?php endif; ?>
                    <?php renderTeamContact($member); ?>
                    <?php if (!empty($member['bio'])): ?><p class="team-modal-bio text-start mt-3"><?= nl2br(e($member['bio'])) ?></p><?php endif; ?>
                    <?php if (!empty($member['verify_link'])): ?>
                        <a href="<?= e($member['verify_link']) ?>" target="_blank" rel="noopener noreferrer" class="team-node-verify">
                            <i class="bi bi-patch-check-fill"></i> Profil vérifié
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

$currentPage = 'equipe';
$pageTitle = 'Notre équipe — HITAS';
$pageDescription = "L'équipe HITAS : les personnes qui accompagnent les étudiants de l'admission jusqu'au départ vers l'institution partenaire.";
applySeoOverrides(getPageSeo('equipe'));
$pageBackgroundKey = 'equipe';
require __DIR__ . '/includes/site_header.php';
?>

<section class="py-5 py-lg-6 content-section reveal">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="mb-4">
            <ol class="breadcrumb hitas-breadcrumb hitas-breadcrumb--light">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Équipe</li>
            </ol>
        </nav>
        <div class="text-center mb-5">
            <span class="section-eyebrow"><?= e(getSetting('equipe_hero_eyebrow', "L'équipe HITAS")) ?></span>
            <h1 class="h2 fw-bold mt-2"><?= e(getSetting('equipe_hero_title', 'Les personnes qui font HITAS')) ?></h1>
            <p class="text-muted col-lg-7 mx-auto"><?= e(getSetting('equipe_hero_lead', "Une équipe engagée auprès des étudiants, de l'admission jusqu'à l'obtention du diplôme.")) ?></p>
        </div>

        <?php if (empty($team)): ?>
            <div class="alert alert-warning text-center">Aucun membre de l'équipe publié pour le moment.</div>
        <?php else: ?>
            <div class="team-tree">

                <?php if ($tiers['gold']): ?>
                <div class="team-tier team-tier--gold">
                    <div class="team-tier-header">
                        <span class="team-tier-badge">🥇 Direction</span>
                        <p class="team-tier-caption">Présidence et vice-présidence de HITAS</p>
                    </div>
                    <div class="team-tier-row">
                        <?php foreach ($tiers['gold'] as $member): ?>
                            <div class="team-node team-node--gold">
                                <?php if (!empty($member['photo'])): ?>
                                    <img src="<?= e(UPLOAD_URL . 'team/' . $member['photo']) ?>" alt="<?= e($member['full_name']) ?>" class="team-node-photo" loading="lazy">
                                <?php else: ?>
                                    <div class="team-node-photo team-node-photo--placeholder"><?= e(teamInitials($member['full_name'])) ?></div>
                                <?php endif; ?>
                                <h2 class="team-node-name"><?= e($member['full_name']) ?></h2>
                                <?php if (!empty($member['role'])): ?><p class="team-node-role"><?= e($member['role']) ?></p><?php endif; ?>
                                <?php renderTeamContact($member); ?>
                                <?php renderTeamMoreButton($member); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($tiers['gold'] && $tiers['silver']): ?>
                    <div class="team-tree-branch team-tree-branch--gold-silver"><span></span></div>
                <?php endif; ?>

                <?php if ($tiers['silver']): ?>
                <div class="team-tier team-tier--silver">
                    <div class="team-tier-header">
                        <span class="team-tier-badge">🥈 Responsables</span>
                        <p class="team-tier-caption">Registraire et chefs de service</p>
                    </div>
                    <div class="team-tier-row">
                        <?php foreach ($tiers['silver'] as $member): ?>
                            <div class="team-node team-node--silver">
                                <?php if (!empty($member['photo'])): ?>
                                    <img src="<?= e(UPLOAD_URL . 'team/' . $member['photo']) ?>" alt="<?= e($member['full_name']) ?>" class="team-node-photo" loading="lazy">
                                <?php else: ?>
                                    <div class="team-node-photo team-node-photo--placeholder"><?= e(teamInitials($member['full_name'])) ?></div>
                                <?php endif; ?>
                                <h3 class="team-node-name"><?= e($member['full_name']) ?></h3>
                                <?php if (!empty($member['role'])): ?><p class="team-node-role"><?= e($member['role']) ?></p><?php endif; ?>
                                <?php renderTeamContact($member); ?>
                                <?php renderTeamMoreButton($member); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($tiers['silver'] && $tiers['bronze']): ?>
                    <div class="team-tree-branch team-tree-branch--silver-bronze"><span></span></div>
                <?php elseif ($tiers['gold'] && $tiers['bronze'] && !$tiers['silver']): ?>
                    <div class="team-tree-branch team-tree-branch--gold-silver"><span></span></div>
                <?php endif; ?>

                <?php if ($tiers['bronze']): ?>
                <div class="team-tier team-tier--bronze">
                    <div class="team-tier-header">
                        <span class="team-tier-badge">🥉 Équipe</span>
                        <p class="team-tier-caption">Nos collaborateurs au quotidien</p>
                    </div>
                    <div class="team-tier-row team-tier-row--compact">
                        <?php foreach ($tiers['bronze'] as $member): ?>
                            <div class="team-node team-node--bronze">
                                <?php if (!empty($member['photo'])): ?>
                                    <img src="<?= e(UPLOAD_URL . 'team/' . $member['photo']) ?>" alt="<?= e($member['full_name']) ?>" class="team-node-photo" loading="lazy">
                                <?php else: ?>
                                    <div class="team-node-photo team-node-photo--placeholder"><?= e(teamInitials($member['full_name'])) ?></div>
                                <?php endif; ?>
                                <h4 class="team-node-name"><?= e($member['full_name']) ?></h4>
                                <?php if (!empty($member['role'])): ?><p class="team-node-role"><?= e($member['role']) ?></p><?php endif; ?>
                                <?php renderTeamContact($member); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <?php foreach (array_merge($tiers['gold'], $tiers['silver']) as $member): ?>
                <?php renderTeamModal($member); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
