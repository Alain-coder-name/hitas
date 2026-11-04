/* =========================================================
   HITAS — Scripts de l'administration
   ========================================================= */

/* Liste des pays (français) + drapeau — intégrée directement ici (pas de
   fichier séparé à charger/nommer correctement) pour le sélecteur de
   drapeau des fiches Partenaires et Autres partenaires. */
var HITAS_COUNTRY_FLAGS = [
    ["Afghanistan","🇦🇫"],["Afrique du Sud","🇿🇦"],["Albanie","🇦🇱"],["Algérie","🇩🇿"],["Allemagne","🇩🇪"],
    ["Andorre","🇦🇩"],["Angola","🇦🇴"],["Arabie saoudite","🇸🇦"],["Argentine","🇦🇷"],["Arménie","🇦🇲"],
    ["Australie","🇦🇺"],["Autriche","🇦🇹"],["Azerbaïdjan","🇦🇿"],["Bahreïn","🇧🇭"],["Bangladesh","🇧🇩"],
    ["Belgique","🇧🇪"],["Bénin","🇧🇯"],["Bhoutan","🇧🇹"],["Biélorussie","🇧🇾"],["Birmanie","🇲🇲"],
    ["Bolivie","🇧🇴"],["Bosnie-Herzégovine","🇧🇦"],["Botswana","🇧🇼"],["Brésil","🇧🇷"],["Brunei","🇧🇳"],
    ["Bulgarie","🇧🇬"],["Burkina Faso","🇧🇫"],["Burundi","🇧🇮"],["Cambodge","🇰🇭"],["Cameroun","🇨🇲"],
    ["Canada","🇨🇦"],["Cap-Vert","🇨🇻"],["Chili","🇨🇱"],["Chine","🇨🇳"],["Chypre","🇨🇾"],
    ["Colombie","🇨🇴"],["Comores","🇰🇲"],["Congo-Brazzaville","🇨🇬"],["Congo-Kinshasa (RDC)","🇨🇩"],["Corée du Nord","🇰🇵"],
    ["Corée du Sud","🇰🇷"],["Costa Rica","🇨🇷"],["Côte d'Ivoire","🇨🇮"],["Croatie","🇭🇷"],["Cuba","🇨🇺"],
    ["Danemark","🇩🇰"],["Djibouti","🇩🇯"],["Égypte","🇪🇬"],["Émirats arabes unis","🇦🇪"],["Équateur","🇪🇨"],
    ["Érythrée","🇪🇷"],["Espagne","🇪🇸"],["Estonie","🇪🇪"],["Eswatini","🇸🇿"],["États-Unis","🇺🇸"],
    ["Éthiopie","🇪🇹"],["Fidji","🇫🇯"],["Finlande","🇫🇮"],["France","🇫🇷"],["Gabon","🇬🇦"],
    ["Gambie","🇬🇲"],["Géorgie","🇬🇪"],["Ghana","🇬🇭"],["Grèce","🇬🇷"],["Guatemala","🇬🇹"],
    ["Guinée","🇬🇳"],["Guinée-Bissau","🇬🇼"],["Guinée équatoriale","🇬🇶"],["Haïti","🇭🇹"],["Honduras","🇭🇳"],
    ["Hongrie","🇭🇺"],["Inde","🇮🇳"],["Indonésie","🇮🇩"],["Irak","🇮🇶"],["Iran","🇮🇷"],
    ["Irlande","🇮🇪"],["Islande","🇮🇸"],["Israël","🇮🇱"],["Italie","🇮🇹"],["Jamaïque","🇯🇲"],
    ["Japon","🇯🇵"],["Jordanie","🇯🇴"],["Kazakhstan","🇰🇿"],["Kenya","🇰🇪"],["Kirghizistan","🇰🇬"],
    ["Kosovo","🇽🇰"],["Koweït","🇰🇼"],["Laos","🇱🇦"],["Lesotho","🇱🇸"],["Lettonie","🇱🇻"],
    ["Liban","🇱🇧"],["Liberia","🇱🇷"],["Libye","🇱🇾"],["Liechtenstein","🇱🇮"],["Lituanie","🇱🇹"],
    ["Luxembourg","🇱🇺"],["Macédoine du Nord","🇲🇰"],["Madagascar","🇲🇬"],["Malaisie","🇲🇾"],["Malawi","🇲🇼"],
    ["Mali","🇲🇱"],["Malte","🇲🇹"],["Maroc","🇲🇦"],["Maurice","🇲🇺"],["Mauritanie","🇲🇷"],
    ["Mexique","🇲🇽"],["Moldavie","🇲🇩"],["Monaco","🇲🇨"],["Mongolie","🇲🇳"],["Monténégro","🇲🇪"],
    ["Mozambique","🇲🇿"],["Namibie","🇳🇦"],["Népal","🇳🇵"],["Nicaragua","🇳🇮"],["Niger","🇳🇪"],
    ["Nigeria","🇳🇬"],["Norvège","🇳🇴"],["Nouvelle-Zélande","🇳🇿"],["Oman","🇴🇲"],["Ouganda","🇺🇬"],
    ["Ouzbékistan","🇺🇿"],["Pakistan","🇵🇰"],["Panama","🇵🇦"],["Papouasie-Nouvelle-Guinée","🇵🇬"],["Paraguay","🇵🇾"],
    ["Pays-Bas","🇳🇱"],["Pérou","🇵🇪"],["Philippines","🇵🇭"],["Pologne","🇵🇱"],["Portugal","🇵🇹"],
    ["Qatar","🇶🇦"],["République centrafricaine","🇨🇫"],["République dominicaine","🇩🇴"],["République tchèque","🇨🇿"],["Roumanie","🇷🇴"],
    ["Royaume-Uni","🇬🇧"],["Russie","🇷🇺"],["Rwanda","🇷🇼"],["Saint-Marin","🇸🇲"],["Salvador","🇸🇻"],
    ["Sénégal","🇸🇳"],["Serbie","🇷🇸"],["Sierra Leone","🇸🇱"],["Singapour","🇸🇬"],["Slovaquie","🇸🇰"],
    ["Slovénie","🇸🇮"],["Somalie","🇸🇴"],["Soudan","🇸🇩"],["Soudan du Sud","🇸🇸"],["Sri Lanka","🇱🇰"],
    ["Suède","🇸🇪"],["Suisse","🇨🇭"],["Suriname","🇸🇷"],["Syrie","🇸🇾"],["Tadjikistan","🇹🇯"],
    ["Tanzanie","🇹🇿"],["Tchad","🇹🇩"],["Thaïlande","🇹🇭"],["Togo","🇹🇬"],["Tunisie","🇹🇳"],
    ["Turkménistan","🇹🇲"],["Turquie","🇹🇷"],["Ukraine","🇺🇦"],["Uruguay","🇺🇾"],["Vanuatu","🇻🇺"],
    ["Vatican","🇻🇦"],["Venezuela","🇻🇪"],["Vietnam","🇻🇳"],["Yémen","🇾🇪"],["Zambie","🇿🇲"],
    ["Zimbabwe","🇿🇼"]
];
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('is-open');
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth >= 992) return;
            if (!sidebar.classList.contains('is-open')) return;
            if (sidebar.contains(e.target) || e.target === toggleBtn) return;
            sidebar.classList.remove('is-open');
        });

        sidebar.querySelectorAll('a.nav-link:not([data-bs-toggle])').forEach(function (link) {
            link.addEventListener('click', function () {
                sidebar.classList.remove('is-open');
            });
        });
    }

    // Confirmation avant toute suppression (formulaires marqués
    // "js-confirm-delete") — évite les clics accidentels.
    document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Supprimer définitivement cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Sélecteur de drapeau de pays (recherche par nom) — voir
    // assets/js/country-flags-data.js pour la liste des pays.
    initFlagPickers();
});

function initFlagPickers() {
    var pickers = document.querySelectorAll('.flag-picker');
    if (!pickers.length || typeof HITAS_COUNTRY_FLAGS === 'undefined') return;

    function normalize(str) {
        return (str || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    pickers.forEach(function (picker) {
        var search = picker.querySelector('.flag-picker-search');
        var hidden = picker.querySelector('.flag-picker-value');
        var previewEmoji = picker.querySelector('.flag-picker-preview-emoji');
        var list = picker.querySelector('.flag-picker-list');
        if (!search || !hidden || !list) return;

        // Pré-remplit la recherche avec le pays déjà enregistré (édition).
        if (hidden.value) {
            var existing = HITAS_COUNTRY_FLAGS.find(function (c) { return c[1] === hidden.value; });
            if (existing) { search.value = existing[0]; }
        }

        function closeList() {
            list.hidden = true;
            list.innerHTML = '';
        }

        function renderMatches(query) {
            var q = normalize(query);
            var matches = q === ''
                ? HITAS_COUNTRY_FLAGS.slice(0, 8)
                : HITAS_COUNTRY_FLAGS.filter(function (c) { return normalize(c[0]).indexOf(q) !== -1; }).slice(0, 8);

            list.innerHTML = '';
            if (!matches.length) {
                var empty = document.createElement('li');
                empty.className = 'flag-picker-empty';
                empty.textContent = 'Aucun pays trouvé.';
                list.appendChild(empty);
                list.hidden = false;
                return;
            }

            matches.forEach(function (c) {
                var li = document.createElement('li');
                li.innerHTML = '<span class="flag-picker-flag">' + c[1] + '</span> ' + c[0];
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    search.value = c[0];
                    hidden.value = c[1];
                    if (previewEmoji) { previewEmoji.textContent = c[1]; }
                    closeList();
                });
                list.appendChild(li);
            });
            list.hidden = false;
        }

        search.addEventListener('input', function () {
            hidden.value = '';
            if (previewEmoji) { previewEmoji.textContent = ''; }
            renderMatches(search.value);
        });
        search.addEventListener('focus', function () { renderMatches(search.value); });
        search.addEventListener('blur', function () { setTimeout(closeList, 150); });
    });
}
