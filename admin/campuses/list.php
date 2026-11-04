<?php
// Les coordonnées du campus (adresse, téléphones, e-mails, recherche Maps) se
// modifient désormais dans Paramètres > Contact (source unique, table "settings").
// L'ancienne table "campuses" n'existe plus : cette page redirige vers Paramètres.
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();
header('Location: ' . BASE_URL . '/admin/settings/index.php');
exit;
