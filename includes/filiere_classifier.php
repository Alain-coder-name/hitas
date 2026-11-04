<?php
// =========================================================
// Classification intelligente des filières — aide à la saisie admin
// -----------------------------------------------------------
// Ce fichier ne crée JAMAIS de filière automatiquement : il se contente
// d'analyser un nom de filière tapé par l'administrateur et de proposer
// le groupe le plus probable parmi les 6 groupes officiels, avec un
// niveau de confiance. L'administrateur reste toujours seul décideur du
// groupe final enregistré (voir admin/programs/suggest_group.php et le
// JS de admin/programs/form.php).
// =========================================================

/** Les 6 groupes officiels, dans l'ordre d'affichage voulu. Liste fermée :
 *  n'en ajoutez pas d'autres sans mettre aussi à jour ce fichier. */
function filiereGroups(): array
{
    return [
        'Ingénierie & Technologies',
        'Management & Commerce',
        'Sciences & Santé',
        'Arts, Design & Culture',
        'Sciences humaines, Droit & Communication',
        'Agriculture, Environnement & Tourisme',
    ];
}

/** Minuscules, sans accents, ponctuation réduite à des espaces simples. */
function normalizeFiliereText(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $replacements = [
        '/[àâä]/u' => 'a', '/[éèêë]/u' => 'e', '/[îï]/u' => 'i',
        '/[ôö]/u' => 'o', '/[ùûü]/u' => 'u', '/[ç]/u' => 'c',
        '/œ/u' => 'oe', '/æ/u' => 'ae',
        '/[^a-z0-9]+/u' => ' ',
    ];
    $text = preg_replace(array_keys($replacements), array_values($replacements), $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

/**
 * Dictionnaire de signaux par groupe.
 * - 'compounds' : expressions de 2 mots ou plus, très fiables (poids fort).
 *   Un mot isolé et ambigu comme « conception » ne suffit jamais seul —
 *   il ne devient fiable qu'associé à un second mot (« conception
 *   mécanique » vs « conception graphique »).
 * - 'strong'    : mots seuls spécifiques à un seul domaine.
 * - 'generic'   : mots seuls qui existent dans plusieurs domaines
 *   (ex. « gestion », « affaires ») : jamais suffisants seuls pour une
 *   confiance élevée.
 */
function filiereClassifierSignals(): array
{
    return [
        'Ingénierie & Technologies' => [
            'compounds' => [
                'genie civil', 'genie electrique', 'genie mecanique', 'genie industriel',
                'genie informatique', 'genie logiciel', 'genie biomedical', 'genie biomedicale',
                'energies renouvelables', 'conception mecanique', 'conception industrielle',
                'conception numerique', 'reseaux et telecommunications', 'reseaux informatiques',
                'ingenierie des systemes', 'systemes intelligents', 'developpement des vehicules',
                'developpement automobile', 'ingenierie du web', 'informatique et ingenierie',
                'sciences de l ingenieur', 'science des donnees', 'sciences des donnees',
                'amenagement du territoire', 'science des materiaux', 'sciences des materiaux',
            ],
            'strong' => [
                'genie', 'informatique', 'electronique', 'automatique', 'architecture',
                'robotique', 'mecatronique', 'ingenierie', 'telecommunications', 'mecanique',
                'electrique', 'industriel', 'industrielle', 'logiciel', 'systemes', 'urbanisme',
                'instrumentation', 'materiaux',
            ],
            'generic' => ['reseaux', 'construction', 'energie', 'technologie', 'technologies', 'paysage'],
        ],
        'Management & Commerce' => [
            'compounds' => [
                'commerce international', 'ressources humaines', 'gestion de projet',
                'gestion de projets', 'gestion des risques', 'administration des entreprises',
                'banque et assurance', 'controle de gestion', 'logistique commerciale',
                'logistique et transport', 'marketing digital', 'gestion des affaires',
                'strategie d entreprise', 'gestion de patrimoine', 'gestion du patrimoine',
                'administration des affaires',
            ],
            'strong' => [
                'management', 'comptabilite', 'finance', 'marketing', 'entrepreneuriat',
                'logistique', 'banque', 'assurance', 'commerce', 'audit', 'economie',
                'administration',
            ],
            'generic' => ['gestion', 'affaires', 'business', 'transport', 'projet', 'entreprise'],
        ],
        'Sciences & Santé' => [
            'compounds' => [
                'soins infirmiers', 'sante publique', 'biologie moleculaire',
                'biotechnologie medicale', 'sciences de la terre', 'chirurgie buccale',
                'medecine et chirurgie', 'technologies de l information biomedicale',
                'hygiene dentaire', 'radiologie medicale', 'imagerie medicale',
                'gestion des services de sante', 'nutrition humaine', 'implants dentaires',
                'esthetique du visage', 'sciences naturelles', 'technique de prevention',
                'techniques de prevention', 'optique et optometrie', 'controle qualite',
                'sciences motrices', 'activites motrices', 'education motrice',
                'sage femme',
            ],
            'strong' => [
                'medecine', 'biologie', 'chimie', 'physique', 'mathematiques', 'statistiques',
                'pharmacie', 'kinesitherapie', 'physiotherapie', 'nutrition', 'biochimie',
                'biotechnologie', 'dentisterie', 'chirurgie', 'obstetrique', 'radiologie',
                'infirmier', 'infirmiere', 'sante', 'medical', 'medicale', 'dentaire',
                'dietetique', 'biomedical', 'biomedicale', 'orthophonie', 'orthoptie',
                'ophtalmologique', 'ophtalmologie', 'optometrie', 'biologique', 'chimique',
                'geologie', 'geologique', 'nutraceutique', 'readaptation', 'audiometrique',
                'audioprothetique', 'physiopathologie', 'cardiocirculatoire',
                'neurophysiopathologie', 'orthopedique', 'ergotherapie', 'toxicologie',
                'toxicologique', 'volcanologie',
            ],
            'generic' => ['clinique', 'hygiene'],
        ],
        'Arts, Design & Culture' => [
            'compounds' => [
                'design graphique', 'conception graphique', 'design d interieur',
                'arts numeriques', 'patrimoine culturel', 'conservation du patrimoine',
                'arts du spectacle', 'arts plastiques', 'metiers de la culture', 'film et son',
                'film sound', 'cinema et audiovisuel', 'gestion de patrimoine', 'gestion du patrimoine',
                'conception editoriale',
            ],
            'strong' => [
                'musique', 'cinema', 'photographie', 'mode', 'design', 'patrimoine', 'film',
                'archeologie',
            ],
            'generic' => ['art', 'arts', 'culture', 'audiovisuel', 'creation'],
        ],
        'Sciences humaines, Droit & Communication' => [
            'compounds' => [
                'relations internationales', 'relations publiques', 'science politique',
                'sciences politiques', 'administration publique', 'communication digitale',
                'langues etrangeres', 'sciences de l education', 'droit des affaires',
                'droit international', 'service social', 'services sociaux',
                'services educatifs', 'innovation sociale', 'travail social',
                'education civique',
            ],
            'strong' => [
                'droit', 'sociologie', 'psychologie', 'philosophie', 'histoire', 'geographie',
                'anthropologie', 'diplomatie', 'journalisme', 'linguistique', 'traduction',
                'interpretation', 'communication', 'avocat', 'jurisprudence', 'pedagogie',
                'pedagogique', 'philologie', 'litterature', 'litteratures', 'civilisation',
                'civilisations', 'lettres', 'criminologie', 'criminologique', 'juridique',
                'juridiques', 'psychologique', 'historique',
            ],
            'generic' => ['langue', 'langues', 'politique', 'medias', 'publicite', 'citoyennete'],
        ],
        'Agriculture, Environnement & Tourisme' => [
            'compounds' => [
                'gestion des ressources naturelles', 'sciences de l environnement',
                'gestion environnementale', 'developpement durable', 'gestion des dechets',
                'conservation de la biodiversite', 'gestion touristique', 'peche et aquaculture',
                'gestion des voyages', 'voyages et loisirs', 'gestion hoteliere',
            ],
            'strong' => [
                'agronomie', 'agriculture', 'elevage', 'foresterie', 'aquaculture', 'tourisme',
                'hotellerie', 'restauration', 'biodiversite', 'environnement', 'agraire',
                'agraires', 'forestier', 'forestiere', 'gastronomie', 'gastronomique',
                'viticulture', 'oenologie',
            ],
            'generic' => ['evenementiel', 'voyages', 'loisirs', 'durable'],
        ],
    ];
}

/**
 * Dictionnaire de correspondance français -> anglais, pour que la
 * recherche de filières comprenne aussi un mot-clé tapé en anglais (ex.
 * « computer science » retrouve « Informatique »). Clé = expression
 * française normalisée (sans accent) recherchée dans le titre, valeur =
 * mots anglais équivalents à ajouter à l'index de recherche de cette
 * filière. Les expressions composées sont volontairement placées avant
 * les mots seuls : peu importe l'ordre ici puisque toutes les
 * correspondances sont cumulées (pas de premier-match-gagnant).
 */
function filiereEnglishSynonyms(): array
{
    return [
        'genie logiciel' => 'software engineering',
        'genie civil' => 'civil engineering',
        'genie electrique' => 'electrical engineering',
        'genie mecanique' => 'mechanical engineering',
        'genie industriel' => 'industrial engineering',
        'genie biomedical' => 'biomedical engineering',
        'genie biomedicale' => 'biomedical engineering',
        'genie chimique' => 'chemical engineering',
        'genie' => 'engineering',
        'ingenierie' => 'engineering',
        'informatique' => 'computer science computing it software',
        'reseaux' => 'networks networking',
        'intelligence artificielle' => 'artificial intelligence ai',
        'science des donnees' => 'data science',
        'sciences des donnees' => 'data science',
        'cybersecurite' => 'cybersecurity security',
        'architecture' => 'architecture',
        'medecine' => 'medicine',
        'chirurgie' => 'surgery',
        'pharmacie' => 'pharmacy',
        'infirmier' => 'nursing nurse',
        'infirmiere' => 'nursing nurse',
        'soins infirmiers' => 'nursing',
        'dentaire' => 'dental dentistry',
        'kinesitherapie' => 'physiotherapy',
        'physiotherapie' => 'physiotherapy',
        'nutrition' => 'nutrition dietetics',
        'veterinaire' => 'veterinary',
        'sante' => 'health',
        'droit' => 'law',
        'avocat' => 'lawyer',
        'gestion' => 'management',
        'management' => 'management',
        'commerce' => 'business trade commerce',
        'affaires' => 'business',
        'comptabilite' => 'accounting',
        'finance' => 'finance',
        'marketing' => 'marketing',
        'economie' => 'economics economy',
        'logistique' => 'logistics supply chain',
        'ressources humaines' => 'human resources hr',
        'tourisme' => 'tourism',
        'hotellerie' => 'hospitality hotel management',
        'agriculture' => 'agriculture farming',
        'agronomie' => 'agronomy',
        'environnement' => 'environment environmental',
        'sciences politiques' => 'political science',
        'science politique' => 'political science',
        'sociologie' => 'sociology',
        'psychologie' => 'psychology',
        'philosophie' => 'philosophy',
        'communication' => 'communication',
        'journalisme' => 'journalism',
        'relations internationales' => 'international relations',
        'traduction' => 'translation',
        'musique' => 'music',
        'design' => 'design',
        'mode' => 'fashion',
        'photographie' => 'photography',
        'cinema' => 'cinema film movie',
        'patrimoine' => 'heritage',
        'histoire' => 'history',
        'geographie' => 'geography',
        'mathematiques' => 'mathematics math maths',
        'physique' => 'physics',
        'chimie' => 'chemistry',
        'biologie' => 'biology',
        'biotechnologie' => 'biotechnology',
    ];
}

/**
 * Texte de recherche « enrichi » d'un titre de filière : le titre
 * normalisé (sans accent/casse), complété par les mots anglais
 * équivalents à tout ce qui y est détecté — pour qu'une recherche tapée
 * en anglais retrouve quand même la filière (voir filiereEnglishSynonyms).
 * Utilisé pour indexer la recherche (jamais pour l'affichage : le titre
 * original reste inchangé à l'écran).
 */
function filiereSearchKeywords(string $title): string
{
    $normalized = normalizeFiliereText($title);
    $extra = [];
    foreach (filiereEnglishSynonyms() as $frTerm => $enWords) {
        if (str_contains($normalized, $frTerm)) {
            $extra[] = $enWords;
        }
    }
    return trim($normalized . ' ' . implode(' ', $extra));
}

/**
 * Analyse un nom de filière et propose un groupe.
 *
 * Retourne toujours :
 * - group        : string|null  (un des 6 groupes officiels, ou null si indéterminé)
 * - confidence    : 'high' | 'medium' | 'low'
 * - alternatives : string[]     (autres groupes plausibles, pour confiance moyenne)
 * - matched       : string[]    (mots/expressions ayant déclenché la suggestion, pour la transparence)
 *
 * Les expressions composées (2+ mots) sont toujours vérifiées AVANT les
 * mots seuls : c'est ce qui permet de distinguer « conception mécanique »
 * (Ingénierie) de « conception graphique » (Arts) sans jamais se fier au
 * mot « conception » tout seul.
 */
function classifyFiliereGroup(string $name): array
{
    $normalized = normalizeFiliereText($name);
    if ($normalized === '') {
        return ['group' => null, 'confidence' => 'low', 'alternatives' => [], 'matched' => []];
    }

    $signals = filiereClassifierSignals();

    // 1) Expressions composées — le signal le plus fiable.
    $compoundScores = [];
    $compoundMatches = [];
    foreach ($signals as $group => $lists) {
        foreach ($lists['compounds'] as $phrase) {
            if (strpos($normalized, $phrase) !== false) {
                $compoundScores[$group] = ($compoundScores[$group] ?? 0) + 3;
                $compoundMatches[$group][] = $phrase;
            }
        }
    }

    if ($compoundScores) {
        arsort($compoundScores);
        $top = array_key_first($compoundScores);
        $topScore = $compoundScores[$top];
        $tied = array_keys(array_filter($compoundScores, fn($s) => $s === $topScore));

        if (count($tied) === 1) {
            return ['group' => $top, 'confidence' => 'high', 'alternatives' => [], 'matched' => $compoundMatches[$top]];
        }
        // Deux groupes (ou plus) aussi précis l'un que l'autre : cas
        // volontairement ambigu (ex. « gestion de patrimoine »).
        return ['group' => $tied[0], 'confidence' => 'medium', 'alternatives' => array_slice($tied, 1), 'matched' => array_merge(...array_values(array_intersect_key($compoundMatches, array_flip($tied))))];
    }

    // 2) Mots seuls (uniquement si aucune expression composée n'a matché).
    $wordScores = [];
    $wordMatches = [];
    foreach ($signals as $group => $lists) {
        $best = 0;
        $matchedWords = [];
        foreach ([2 => $lists['strong'], 1 => $lists['generic']] as $weight => $words) {
            foreach ($words as $word) {
                // Tolère les pluriels simples en français (mot + s/es) sans
                // dictionnaire de flexion complet — suffisant pour cet usage.
                if (preg_match('/(?:^| )' . preg_quote($word, '/') . '(?:es|s)?(?:$| )/', $normalized)) {
                    $best = max($best, $weight);
                    $matchedWords[] = $word;
                }
            }
        }
        if ($best > 0) {
            $wordScores[$group] = $best;
            $wordMatches[$group] = $matchedWords;
        }
    }

    if (!$wordScores) {
        return ['group' => null, 'confidence' => 'low', 'alternatives' => [], 'matched' => []];
    }

    arsort($wordScores);
    $top = array_key_first($wordScores);
    $topScore = $wordScores[$top];
    $others = array_keys($wordScores);
    unset($others[array_search($top, $others, true)]);
    $others = array_values($others);

    // Confiance élevée seulement si un mot spécifique (poids 2) a matché
    // ET qu'aucun autre groupe n'a un score aussi fort.
    $secondScore = $others ? $wordScores[$others[0]] : 0;
    if ($topScore >= 2 && $secondScore < 2) {
        return ['group' => $top, 'confidence' => 'high', 'alternatives' => [], 'matched' => $wordMatches[$top]];
    }

    // Sinon : un seul signal faible, ou plusieurs groupes à égalité —
    // dans les deux cas, on demande une confirmation.
    return [
        'group' => $top,
        'confidence' => 'medium',
        'alternatives' => $others,
        'matched' => $wordMatches[$top],
    ];
}
