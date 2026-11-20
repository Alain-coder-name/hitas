# HITAS — Site complet (public + back-office)

Institut Hanséatique de Technologie et de Sciences Appliquées

Ce livrable contient la **base de données complète**, le **panneau d'administration fonctionnel** et le **site public** (Bootstrap), prêts à être hébergés.

> 🔒 **Sécurité d'abord** : ce dépôt ne contient et ne doit jamais contenir de mot de passe, d'identifiant de base de données ou de clé secrète en clair. Lisez la section 2 avant toute mise en ligne.

## 1. Installation

1. **Préparer le compte administrateur (avant l'import)**
   Générez le hash d'un mot de passe fort (16 caractères minimum, unique, gardé dans un gestionnaire de mots de passe) :
   ```
   php -r "echo password_hash(readline('Mot de passe : '), PASSWORD_DEFAULT), PHP_EOL;"
   ```
   Copiez le hash obtenu dans `database/hitas_complete.sql`, à la place du hash du compte `admin` fourni, **avant** l'import. Ne notez jamais le mot de passe lui-même dans un fichier du projet.

2. Créer une base MySQL et importer **le seul et unique fichier** `database/hitas_complete.sql` (structure + contenu réel) :
   ```
   mysql -u root -p < database/hitas_complete.sql
   ```
   Il recrée tout de zéro : faites-le sur une base vide (ou supprimez d'abord `hitas_db` si elle existe).

   Si vous avez un site **déjà en ligne**, exécutez une seule fois `database/migration_lot_b.sql` (tables et colonnes du Lot B) sans toucher au contenu existant.

3. **Configurer la connexion à la base — sans la commiter**
   - Copiez `config/database.example.php` en `config/database.php` (ou `.env.example` en `.env`) et renseignez vos valeurs :
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'hitas_db');
     define('DB_USER', 'utilisateur_dedie');
     define('DB_PASS', 'mot_de_passe_long_et_unique');
     ```
   - Ajoutez au `.gitignore` : `config/database.php`, `.env`, `uploads/*` (sauf un `.gitkeep`).
   - Créez un **utilisateur MySQL dédié** au site, avec uniquement les droits `SELECT, INSERT, UPDATE, DELETE` sur `hitas_db` (jamais `root`).

4. Placer le dossier `hitas/` sur votre serveur web (`htdocs`, `www`...). Le site détecte automatiquement s'il est à la racine ou dans un sous-dossier (constante `BASE_URL`, voir `config/database.php`). Les fichiers `.htaccess` à la racine et dans `database/`, `config/` et `includes/` bloquent l'accès web direct à ces dossiers : **ne les supprimez pas**.

5. Vérifier que `uploads/` est accessible en écriture par le serveur : permissions **755 (dossiers) / 644 (fichiers)**, jamais 777.

6. Accéder à `/admin/login.php` avec le compte créé à l'étape 1.

7. **Après l'installation, supprimez du serveur** : `database/hitas_complete.sql`, `database/migration_lot_b.sql`, `database/add_faq_seed.sql` (ils contiennent la structure complète et des hashs). Gardez-les uniquement en local ou dans un stockage privé.

## 2. Sécurité et accès administrateur

**Il n'y a pas de mot de passe par défaut.** Le mot de passe est celui que vous avez défini à l'étape 1 de l'installation.

À faire impérativement avant la mise en ligne :

- **Mot de passe fort et unique** pour chaque compte admin (16+ caractères). Pas de mot de passe réutilisé, pas de mot de passe dans le README, un e-mail ou une conversation.
- **Renommer le compte `admin`** (créer un super-admin avec un identifiant non devinable, puis supprimer l'ancien) via *Utilisateurs admin*.
- **HTTPS obligatoire** : installez un certificat (Let's Encrypt est gratuit) et forcez la redirection HTTP → HTTPS dans `.htaccess`.
- **Limiter les tentatives de connexion** (blocage temporaire après 5 échecs) et idéalement ajouter une double authentification (2FA).
- **Protéger `/admin/`** en plus : restriction par adresse IP dans `.htaccess` ou authentification HTTP supplémentaire si possible.
- **Cookies de session sécurisés** : `session.cookie_httponly=1`, `session.cookie_secure=1`, `session.cookie_samesite=Strict`, et régénérer l'ID de session après connexion.
- **Masquer les erreurs en production** : `display_errors = Off` (les erreurs vont dans un journal, pas à l'écran).
- **Requêtes préparées (PDO/MySQLi)** partout et échappement des sorties (`htmlspecialchars`) pour éviter injections SQL et XSS.
- **Sauvegardes régulières** de la base et de `uploads/`, stockées hors du serveur web.
- **Mises à jour** : PHP, MySQL et le serveur à jour.
- **Aucun secret dans Git** : si un mot de passe ou une clé a déjà été commité ou partagé, considérez-le comme compromis et changez-le immédiatement (le supprimer du fichier ne suffit pas, l'historique Git le conserve).

## 3. Structure du projet

```
hitas/
├── admin/              → tout le panneau d'administration
│   ├── home/             (contenu texte de l'accueil : hero, piliers, CTA)
│   ├── home_sections/    (activation/réordonnancement des blocs de l'accueil, drag & drop)
│   ├── hero_slides/      (diaporama d'accueil : image ou vidéo, titre/sous-titre/bouton, durée)
│   ├── stats/            (chiffres clés de l'accueil, CRUD libre)
│   ├── programs/         (programmes de formation)
│   ├── partners/         (universités partenaires)
│   ├── news/             (actualités)
│   ├── team/             (équipe)
│   ├── testimonials/     (témoignages)
│   ├── gallery/          (galerie photo + médiathèque unifiée, voir §11)
│   ├── faq/              (FAQ)
│   ├── banners/          (bandeaux des pages Programmes/Partenaires/Équipe/Actualités/Postulez)
│   ├── pages/            (pages statiques : À propos, Mission, Admissions...)
│   ├── messages/         (messages reçus depuis le site public)
│   ├── settings/         (paramètres généraux : logo, couleurs, contacts)
│   └── users/            (gestion des comptes admin — réservé super-admin)
├── config/database.php  → connexion à la base (NON commité, voir .gitignore)
├── config/database.example.php → modèle avec valeurs factices (commité)
├── includes/auth.php    → authentification, sécurité (CSRF), upload d'images et de vidéos
├── assets/               → CSS/JS de l'admin
├── uploads/              → images/vidéos uploadées (protégées contre l'exécution PHP)
└── database/             → fichiers SQL d'installation (à supprimer du serveur après usage)
```

## 4. Fonctionnement de chaque module

Chaque module suit le même schéma :
- `list.php` — liste des éléments avec édition/suppression
- `form.php` (ou `add.php` / `edit.php`) — formulaire d'ajout ou modification
- `save.php` — traitement du formulaire
- `delete.php` — suppression sécurisée

Tous les formulaires sont protégés par jeton CSRF. Les uploads sont validés côté serveur (type MIME réel, extension, taille maximale) : images jpg, jpeg, png, webp ; vidéos mp4, webm. Les fichiers sont renommés à l'upload et le dossier `uploads/` n'exécute jamais de PHP.

## 5. Rôles

- **super_admin** : accès à tout, y compris la gestion des comptes admin.
- **editor** (rédacteur) : accès à tout le contenu, sauf la gestion des utilisateurs admin.

Donnez à chaque personne le rôle minimal nécessaire et supprimez les comptes qui ne servent plus.

## 6. Publicités dans les actualités

Chaque article peut être marqué « Actualité » ou « Publicité ». Pour une publicité, le champ **Lien externe de destination** est obligatoire : le visiteur est redirigé vers cette URL dans un nouvel onglet (`rel="noopener noreferrer"`), depuis l'accueil ou la page Actualités. Une publicité sans lien externe est refusée à l'enregistrement. Seules les URL en `https://` doivent être acceptées.

## 7. Pilotage de la page d'accueil (Lot B)

- **Sections & blocs** (`/admin/home_sections/list.php`) : chaque bloc de l'accueil (Hero, Chiffres clés, Filières phares, Partenaires, Piliers, Actualités, Témoignages, CTA final) peut être désactivé et réordonné par glisser-déposer ; l'ordre est enregistré automatiquement.
- **Diaporama accueil** (`/admin/hero_slides/list.php`) : chaque slide accepte une image ou une vidéo courte (mp4/webm, silencieuse, en boucle), un titre, un sous-titre, un bouton avec lien et sa durée d'affichage. Si le titre est vide, le texte par défaut du bloc *Accueil* reste affiché.
- **Chiffres clés** (`/admin/stats/list.php`) : CRUD libre (valeur, libellé, icône), réordonnables. Si aucun chiffre n'est actif, le site affiche les compteurs calculés (programmes/partenaires/pays publiés).
- Le menu de gauche regroupe Programmes / Partenaires / Actualités / Équipe / Témoignages / FAQ / Pages sous l'entrée dépliable **« Contenu »**.

## 8. FAQ et bandeaux de pages

- La page publique `/faq.php` affiche les questions publiées (module *FAQ*) sous forme d'accordéon. Elle n'est pas dans le menu principal mais est reliée à la page **Admissions** et à Postulez, et reste accessible directement et via le plan du site.
- Un contenu FAQ de départ est inclus dans `database/hitas_complete.sql`. Sur un site déjà en ligne, exécutez `database/add_faq_seed.sql` une seule fois, puis supprimez-le du serveur.
- Le module *Bandeaux des pages* (`/admin/banners/index.php`) permet de modifier titre, eyebrow et accroche des pages Programmes, Partenaires, Équipe, Actualités et Postulez.

## 9. Galerie & Médiathèque unifiée (Lot C)

Le module *Galerie* (`/admin/gallery/list.php`) contient deux onglets :

- **Galerie** : photos « vie étudiante / campus » avec titre, catégorie et lien optionnel vers un programme ou un partenaire.
- **Médiathèque** (`/admin/gallery/media.php`) : vue globale de tous les fichiers uploadés, tous modules confondus. Pour chaque fichier :
  - **Recherche et filtres** : nom, dossier d'origine, type (image/vidéo), statut d'utilisation.
  - **Traçage d'usage** : le bouton *« Utilisé (n) »* détaille les contenus qui référencent le fichier. Un fichier sans référence est marqué **Orphelin**.
  - **Suppression sécurisée** : refusée tant qu'un contenu utilise le fichier.
  - **Remplacement en place** : un nouvel upload écrase l'ancien (même nom, même emplacement), sans toucher à la base.
  - **Ajout libre** : ajout direct dans un dossier avant utilisation.

Aucune nouvelle table n'est requise : la médiathèque relit en temps réel les colonnes existantes (`programs.image`, `news.image`, `partners.logo`, etc.).

## 10. Checklist avant la mise en ligne définitive

- [ ] Mot de passe admin fort défini avant l'import, compte `admin` renommé
- [ ] `config/database.php` renseigné avec un utilisateur MySQL dédié, et absent de Git
- [ ] HTTPS actif et forcé
- [ ] `display_errors = Off`
- [ ] Fichiers `.sql` supprimés du serveur
- [ ] `.htaccess` (racine, `database/`, `config/`, `includes/`) présents et fonctionnels
- [ ] Permissions 755/644, aucun dossier en 777
- [ ] Sauvegardes automatiques configurées
- [ ] Les 14 programmes en brouillon complétés (durée, description) puis publiés
