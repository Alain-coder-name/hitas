<?php
// Endpoint AJAX : suggère un groupe (parmi les 6 groupes officiels) pour un
// nom de filière tapé dans le formulaire d'ajout/modification. Ne crée et
// n'enregistre jamais rien — pure suggestion, lecture seule.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/filiere_classifier.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$name = trim($_GET['name'] ?? '');
if ($name === '') {
    echo json_encode(['group' => null, 'confidence' => 'low', 'alternatives' => [], 'matched' => []]);
    exit;
}

echo json_encode(classifyFiliereGroup($name), JSON_UNESCAPED_UNICODE);
