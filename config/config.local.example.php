<?php
// =========================================================
// MODÈLE de configuration pour l'hébergement en ligne.
//
// 1. Copiez ce fichier sous le nom  config/config.local.php
// 2. Remplissez les valeurs fournies par votre hébergeur
// 3. Ne mettez JAMAIS ce fichier ailleurs que sur le serveur
//
// (config/ est interdit d'accès depuis le web grâce à son .htaccess.)
// =========================================================

// Base de données : un utilisateur DÉDIÉ au site, avec un mot de passe long et
// unique — jamais « root ». Droits suffisants : SELECT, INSERT, UPDATE, DELETE,
// CREATE (pour la table de sécurité security_events créée automatiquement).
define('DB_HOST', 'localhost');
define('DB_NAME', 'nom_de_la_base');
define('DB_USER', 'utilisateur_dedie');
define('DB_PASS', 'mot_de_passe_long_et_unique');

// Redirection automatique http -> https (activée par défaut dès que le site n'est
// plus en local). Mettez false uniquement si votre hébergeur n'a pas encore le HTTPS.
// define('HITAS_FORCE_HTTPS', true);

// Derrière Cloudflare / un proxy : en-tête qui contient la vraie IP du visiteur
// (utilisée par la limitation anti brute-force). Laisser commenté sinon.
// define('HITAS_TRUSTED_PROXY_HEADER', 'CF-Connecting-IP');
