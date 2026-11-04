# Modifications du site — Étape 1 : Accueil + Footer

## Comment déployer
Remplace sur le serveur en ligne chacun des fichiers ci-dessous par celui du même
chemin dans cette archive (mêmes emplacements que sur le site actuel). Aucun autre
fichier n'a été touché.

```
hitas/index.php
hitas/includes/site_header.php
hitas/includes/site_footer.php
hitas/assets/css/site.css
hitas/assets/js/site.js
hitas/admin/settings/index.php
hitas/admin/settings/save.php
```

## Détail des changements par fichier

### index.php
- Bouton « Nous contacter » du hero → ancre vers le Footer (défilement fluide).
- Requête programmes : récupère désormais TOUS les programmes publiés (au lieu
  de 3), regroupés par 3 pour le diaporama « Filières phares ».
- Nouveau bloc « Actualités » carré à droite du texte principal du hero
  (photo/vidéo + titre + « Plus de détails »), diaporama automatique.
- Filières phares : diaporama automatique par groupes de 3.
- Bloc avant le Footer : « Nous contacter » remplacé par « Quelle filière
  veux-tu étudier ? » → lien vers programmes.php.
- L'annonce existante et le bloc Actualités déjà présent plus bas sur la page
  n'ont pas été déplacés ni modifiés.

### includes/site_header.php
- Texte « Technology & Applied Sciences » supprimé.
- Texte « HITAS » agrandi.
- Logo graphique HITAS non touché (taille, position, image inchangées).
- Bouton et popup de sélection de campus (Douala / Yaoundé) supprimés de la
  navbar et de l'entrée du site.

### includes/site_footer.php
- Ajout d'un ancrage `#footer-phone-contact` sur le bloc Contact.
- Ajout de l'affichage de la Boîte postale (si renseignée dans les réglages
  admin).
- Suppression de la mention « Campus de Douala / Yaoundé » à côté du titre
  Contact.

### assets/css/site.css
- Statistiques (14 / 8 / 5) réduites.
- Correction de l'affichage « À propos » sur une seule ligne dans la navbar.
- Suppression du filtre gris/monochrome sur les logos des universités
  partenaires (couleurs d'origine conservées).
- Nouveaux styles pour les diaporamas Actualités (hero) et Filières phares,
  responsives desktop / tablette / mobile, sans saut de mise en page.
- Nouveau style pour le lien « Quelle filière veux-tu étudier ? ».

### assets/js/site.js
- Logique du diaporama Actualités du hero (autoplay, transitions fluides,
  gestion image ET vidéo, points de pagination).
- Logique du diaporama Filières phares (rotation automatique par groupes de
  3, fluide, sans déformation de page).
- Les deux diaporamas respectent `prefers-reduced-motion`.

### admin/settings/index.php + admin/settings/save.php
- Ajout d'un champ « Boîte postale » dans les réglages généraux (nécessaire
  pour pouvoir renseigner la valeur affichée dans le Footer).

## À vérifier après déploiement
- Diaporama Actualités et diaporama Filières phares avec 0, 1 ou plusieurs
  éléments.
- Défilement fluide du bouton « Nous contacter » vers le Footer sur mobile.
- Renseigner la Boîte postale dans l'admin (Réglages) pour qu'elle s'affiche
  dans le Footer.
- Aucune page autre qu'Accueil + Footer n'a été modifiée dans cette étape.
