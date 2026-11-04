<?php
// Ancien enregistrement des coordonnées du campus : remplacé par Paramètres > Contact.
require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();
header('Location: ' . BASE_URL . '/admin/settings/index.php');
exit;
