# HITAS — Site complet (public + back-office)

Institut Hanséatique de Technologie et de Sciences Appliquées

Ce livrable contient la **base de données complète**, le **panneau d'administration fonctionnel** et le **site public** (Bootstrap), prêts à être hébergés.

## 1. Installation

1. Créer une base MySQL et importer **le seul et unique fichier** `database/hitas_complete.sql` (structure + contenu réel + mot de passe admin vérifié, tout en un) :
   ```
   mysql -u root -p < database/hitas_complete.sql
   ```
   C'est le seul fichier SQL nécessaire pour une **nouvelle** installation. Il recrée tout de zéro (schéma + contenu), donc si vous réimportez, faites-le sur une base vide (ou droppez d'abord `hitas_db` si elle existe déjà, pour éviter les erreurs "table already exists").

   Si vous avez un site **déjà en ligne** (base existante des Lots précédents) et ne voulez pas tout réimporter, exécutez une seule fois `database/migration_lot_b.sql` : il ajoute les tables et colonnes du Lot B (sections d'accueil pilotables, hero enrichi, chiffres clés) sans toucher au contenu existant.
2. Configurer les identifiants de connexion dans `config/database.php` (remplacer les valeurs de développement par celles de votre hébergeur) :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hitas_db');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   ```
3. Placer le dossier `hitas/` dans votre serveur web (`htdocs`, `www`...). Le site détecte automatiquement s'il est à la racine (hébergement définitif, ex: `https://votredomaine.com/`) ou dans un sous-dossier (test en local, ex: `http://localhost/hitas/`) : tous les liens, CSS, images s'adaptent tout seuls, sans rien à modifier (voir `config/database.php`, constante `BASE_URL`). Le fichier `.htaccess` à la racine, ainsi que ceux dans `database/`, `config/` et `includes/`, bloquent déjà l'accès web direct à ces dossiers sensibles — ne les supprimez pas.
4. Vérifier que le dossier `uploads/` est accessible en écriture par le serveur (permissions 755/775).
5. Accéder à `/admin/login.php`

## 2. Connexion par défaut

| Utilisateur | Mot de passe |
|---|---|
| `admin` | `admin123` |

⚠️ **Changez ce mot de passe immédiatement** via *Utilisateurs admin* une fois connecté, ou en créant un nouveau super-admin puis en supprimant celui-ci.

## 3. Structure du projet

```
hitas/
├── admin/              → tout le panneau d'administration
│   ├── home/             (contenu texte de l'accueil : hero, piliers, CTA)
│   ├── home_sections/    (activation/désactivation et réordonnancement des blocs de l'accueil, drag & drop)
│   ├── hero_slides/      (diaporama d'accueil : image ou vidéo, titre/sous-titre/bouton, durée par slide)
│   ├── stats/            (chiffres clés de l'accueil, CRUD libre)
│   ├── programs/        (programmes de formation)
│   ├── partners/        (universités partenaires)
│   ├── news/             (actualités)
│   ├── team/              (équipe)
│   ├── testimonials/     (témoignages)
│   ├── gallery/          (galerie photo + médiathèque unifiée, voir §10)
│   ├── faq/                (FAQ)
│   ├── banners/          (bandeaux des pages Programmes/Partenaires/Équipe/Actualités/Postulez)
│   ├── pages/            (pages statiques : À propos, Mission, Admissions...)
│   ├── messages/         (messages reçus depuis le site public)
│   ├── settings/         (paramètres généraux : logo, couleurs, contacts)
│   └── users/             (gestion des comptes admin — réservé super-admin)
├── config/database.php  → connexion à la base de données
├── includes/auth.php    → authentification, sécurité (CSRF), upload d'images et de vidéos
├── assets/               → CSS/JS de l'admin
├── uploads/              → images/vidéos uploadées (protégées contre l'exécution PHP)
├── database/hitas_complete.sql → structure ET contenu réel, fichier unique pour une nouvelle installation
└── database/migration_lot_b.sql → migration à jouer une fois sur un site existant (Lot B)
```

## 4. Fonctionnement de chaque module

Chaque module suit le même schéma simple :
- `list.php` — liste des éléments avec édition/suppression
- `form.php` (ou `add.php` / `edit.php`) — formulaire d'ajout ou modification
- `save.php` — traitement du formulaire
- `delete.php` — suppression sécurisée

Tous les formulaires sont protégés par jeton CSRF, et les images uploadées sont validées (jpg, jpeg, png, webp uniquement).

## 5. Rôles

- **super_admin** : accès à tout, y compris la gestion des comptes admin.
- **editor** (rédacteur) : accès à tout le contenu, sauf la gestion des utilisateurs admin.

## 6. Publicités dans les actualités

Dans **Actualités**, chaque article peut être marqué "Actualité" ou "Publicité". Pour une publicité, un champ **Lien externe de destination** apparaît : c'est l'URL (site partenaire, offre, etc.) vers laquelle le visiteur sera redirigé — dans un nouvel onglet — quand il clique sur cette publicité, que ce soit depuis la page d'accueil ou la page Actualités. Une publicité sans lien externe est refusée à l'enregistrement.

## 7. Pilotage de la page d'accueil (Lot B)

- **Sections & blocs** (`/admin/home_sections/list.php`) : chaque bloc de la page d'accueil (Hero, Chiffres clés, Filières phares, Partenaires, Piliers, Actualités, Témoignages, CTA final) peut être **désactivé** (masqué du site public) et **réordonné** par glisser-déposer directement dans la liste. Le nouvel ordre est enregistré automatiquement dès que vous relâchez un bloc.
- **Diaporama accueil** (`/admin/hero_slides/list.php`) : chaque slide accepte une **image ou une vidéo courte** (mp4/webm, silencieuse, en boucle), un **titre**, un **sous-titre**, un **bouton** avec son lien, et sa propre **durée d'affichage**. Si le titre d'une slide est vide, le texte par défaut du bloc *Accueil* (`/admin/home/index.php`) reste affiché pendant cette slide.
- **Chiffres clés** (`/admin/stats/list.php`) : CRUD libre (valeur, libellé, icône). Réordonnables par flèches monter/descendre. Si aucun chiffre n'est actif, le site affiche automatiquement les compteurs calculés (nombre de programmes/partenaires/pays publiés) comme avant.
- Le menu de gauche regroupe désormais Programmes / Partenaires / Actualités / Équipe / Témoignages / FAQ / Pages sous une seule entrée dépliable **« Contenu »**.

## 8. FAQ et bandeaux de pages

- La page publique `/faq.php` affiche les questions publiées (module *FAQ* de l'admin), sous forme d'accordéon. Elle n'est pas dans le menu principal, mais elle est reliée à la page **Admissions** (qui n'y est pas non plus) : un bouton « Des questions ? Consultez la FAQ » y renvoie, et la FAQ renvoie elle-même vers Admissions et Postulez. Elle reste accessible directement (`/faq.php`) et indexée via le plan du site.
- Un contenu FAQ de départ est inclus dans `database/hitas_complete.sql`. Si vous ne réimportez pas toute la base sur un site déjà en ligne, exécutez `database/add_faq_seed.sql` une seule fois pour peupler la page.
- Le module *Bandeaux des pages* (`/admin/banners/index.php`) permet de modifier le titre, le petit texte au-dessus (eyebrow) et l'accroche affichés en haut des pages Programmes, Partenaires, Équipe, Actualités et Postulez — exactement comme le module *Accueil* le fait déjà pour la page d'accueil.

## 10. Galerie & Médiathèque unifiée (Lot C)

Le module *Galerie* (`/admin/gallery/list.php`) contient désormais deux onglets :

- **Galerie** : inchangé, ce sont les photos "vie étudiante / campus" avec titre, catégorie et lien optionnel vers un programme ou un partenaire.
- **Médiathèque** (`/admin/gallery/media.php`) : une **vue globale de tous les fichiers uploadés sur le site**, tous modules confondus (programmes, actualités, pages, partenaires, autres programmes, équipe, témoignages, galerie, diaporama d'accueil, favicon). Pour chaque fichier :
  - **Recherche et filtres** : par nom de fichier, dossier d'origine, type (image/vidéo) et statut d'utilisation.
  - **Traçage d'usage** : le bouton *« Utilisé (n) »* ouvre le détail exact des contenus qui référencent ce fichier (avec lien direct vers la fiche à modifier). Un fichier sans aucune référence est marqué **Orphelin**.
  - **Suppression sécurisée** : la suppression est **automatiquement refusée** tant qu'au moins un contenu utilise le fichier (le message indique précisément où). Seuls les fichiers réellement inutilisés peuvent être supprimés du disque.
  - **Remplacement en place** : uploader un nouveau fichier à la place d'un ancien l'écrase à l'identique (même nom, même emplacement) — toutes les pages qui l'affichaient sont donc mises à jour automatiquement, sans toucher à la base de données.
  - **Ajout libre** : un fichier peut aussi être ajouté directement dans un dossier depuis la médiathèque, avant d'être utilisé plus tard dans un formulaire.

Cette fonctionnalité ne modifie ni ne requiert aucune nouvelle table : elle relit en temps réel les colonnes images/logos/vidéos existantes (`programs.image`, `news.image`, `partners.logo`, etc.) pour établir la correspondance fichier ↔ contenu.

## 11. Avant la mise en ligne définitive

- Changez le mot de passe admin par défaut (`admin` / `admin123`) dès la première connexion.
- Remplacez les identifiants de `config/database.php` par ceux de votre hébergeur.
- Complétez et publiez les 14 programmes actuellement en brouillon (durée, description).
- Ne supprimez pas les fichiers `.htaccess` (racine, `database/`, `config/`, `includes/`) : ils protègent les fichiers sensibles contre un accès direct par navigateur.
