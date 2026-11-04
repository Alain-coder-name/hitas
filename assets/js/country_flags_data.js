/* =========================================================================
   Sélecteur de drapeau de pays (recherche par nom, sans copier-coller
   d'emoji) — liste des pays en français avec leur drapeau. Utilisé par
   initFlagPickers() ci-dessous, sur les fiches Partenaires et Autres
   partenaires.
   ========================================================================= */
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
    ["Royaume-Uni","🇬🇧"],["Russie","🇷🇺"],["Rwanda","🇷🇼"],["Salvador","🇸🇻"],["Sénégal","🇸🇳"],
    ["Serbie","🇷🇸"],["Sierra Leone","🇸🇱"],["Singapour","🇸🇬"],["Slovaquie","🇸🇰"],["Slovénie","🇸🇮"],
    ["Somalie","🇸🇴"],["Soudan","🇸🇩"],["Soudan du Sud","🇸🇸"],["Sri Lanka","🇱🇰"],["Suède","🇸🇪"],
    ["Suisse","🇨🇭"],["Suriname","🇸🇷"],["Syrie","🇸🇾"],["Tadjikistan","🇹🇯"],["Tanzanie","🇹🇿"],
    ["Tchad","🇹🇩"],["Thaïlande","🇹🇭"],["Togo","🇹🇬"],["Tunisie","🇹🇳"],["Turkménistan","🇹🇲"],
    ["Turquie","🇹🇷"],["Ukraine","🇺🇦"],["Uruguay","🇺🇾"],["Vanuatu","🇻🇺"],["Venezuela","🇻🇪"],
    ["Vietnam","🇻🇳"],["Yémen","🇾🇪"],["Zambie","🇿🇲"],["Zimbabwe","🇿🇼"]
];
