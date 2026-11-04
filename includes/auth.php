<?php
// =========================================================
// Authentification & sécurité - Zone Admin HITAS
// =========================================================

// La config (donc includes/security.php : cookie de session HttpOnly/SameSite/Secure,
// en-têtes de sécurité) doit être chargée AVANT le démarrage de la session.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/site_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie que l'utilisateur est connecté, sinon redirige vers le login.
 * À inclure en haut de CHAQUE page admin protégée.
 */
function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    enforceSessionLimits();
    enforceRoleAccess();
}

/**
 * Expire la session admin après 2 h d'inactivité ou 12 h au total, et si le
 * navigateur change (vol de cookie de session). Les sessions ouvertes avant
 * l'ajout de ces contrôles n'ont pas ces marqueurs : elles sont simplement
 * « adoptées » au prochain passage.
 */
function enforceSessionLimits(): void
{
    $now = time();
    $ua  = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

    $expired = false;
    if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > 2 * 3600) {
        $expired = true;
    }
    if (isset($_SESSION['login_time']) && ($now - (int)$_SESSION['login_time']) > 12 * 3600) {
        $expired = true;
    }
    if (isset($_SESSION['admin_ua']) && !hash_equals((string)$_SESSION['admin_ua'], $ua)) {
        $expired = true;
        hitasSecurityLog('session admin rejetée : navigateur différent');
    }

    if ($expired) {
        logout();
        header('Location: ' . BASE_URL . '/admin/login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = $now;
    $_SESSION['login_time']    = $_SESSION['login_time'] ?? $now;
    $_SESSION['admin_ua']      = $_SESSION['admin_ua'] ?? $ua;
}

/**
 * Restreint l'accès aux sections de l'admin selon le rôle connecté.
 * - super_admin : accès total, aucune restriction.
 * - redacteur   : uniquement Filières (ajouter/modifier/supprimer des
 *                 programmes) — rien d'autre.
 * - boss        : tableau de bord (synthèses/statistiques), Actualités
 *                 (peut en ajouter) et Messages (peut intervenir) — rien
 *                 d'autre.
 * Appelée automatiquement par requireLogin(), donc protège TOUTES les
 * pages admin (list/form/save/delete/etc.) sans avoir à modifier chaque
 * fichier individuellement.
 */
function enforceRoleAccess(): void
{
    $role = $_SESSION['admin_role'] ?? '';
    if ($role === 'super_admin') {
        return;
    }

    // Déduit le module courant à partir du chemin de l'URL en cours
    // (ex : /admin/programs/save.php -> "programs").
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $parts = explode('/', trim($scriptPath, '/'));
    $adminIndex = array_search('admin', $parts, true);
    $module = ($adminIndex !== false && isset($parts[$adminIndex + 1])) ? $parts[$adminIndex + 1] : '';

    // Le tableau de bord (admin/index.php) reste accessible à tous les
    // rôles connectés — son contenu s'adapte déjà selon le rôle.
    if ($module === '' || $module === 'index.php') {
        return;
    }

    $allowedByRole = [
        'redacteur' => ['programs'],
        'boss'      => ['news', 'messages'],
    ];

    $allowed = $allowedByRole[$role] ?? [];
    if (!in_array($module, $allowed, true)) {
        http_response_code(403);
        die('<div style="font-family:-apple-system,sans-serif;max-width:480px;margin:100px auto;text-align:center;color:#33414f;">
            <h2 style="color:#002147;">Accès refusé</h2>
            <p>Votre rôle ne permet pas d\'accéder à cette section de l\'administration.</p>
            <a href="' . BASE_URL . '/admin/index.php" style="color:#9c7c3c;font-weight:600;">← Retour au tableau de bord</a>
        </div>');
    }
}

/**
 * Vérifie que l'utilisateur connecté est super_admin (pour la gestion des utilisateurs).
 */
function requireSuperAdmin(): void
{
    requireLogin();
    if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        die('Accès refusé : réservé au super-administrateur.');
    }
}

/**
 * Tente de connecter un utilisateur. Retourne true si succès.
 */
/** Plafonds anti brute-force : échecs sur 15 minutes, par identifiant et par adresse IP. */
const LOGIN_MAX_FAILS_PER_USER = 5;
const LOGIN_MAX_FAILS_PER_IP   = 10;
const LOGIN_WINDOW_SECONDS     = 900;

/** Vrai si trop d'échecs récents : la tentative est refusée sans même tester le mot de passe. */
function loginIsLocked(string $username): bool
{
    $username = mb_strtolower(trim($username));
    return securityCount('login_user', $username, LOGIN_WINDOW_SECONDS) >= LOGIN_MAX_FAILS_PER_USER
        || securityCount('login_ip', hitasClientIp(), LOGIN_WINDOW_SECONDS) >= LOGIN_MAX_FAILS_PER_IP;
}

function attemptLogin(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || mb_strlen($username) > 100 || mb_strlen($password) > 1000) {
        return false;
    }
    if (loginIsLocked($username)) {
        return false;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Même travail de hachage que l'identifiant existe ou non : le temps de
    // réponse ne révèle pas quels comptes existent.
    static $dummyHash = null;
    $dummyHash = $dummyHash ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $valid = password_verify($password, $user['password_hash'] ?? $dummyHash) && $user;

    if ($valid) {
        // Nouvel identifiant de session à la connexion (anti « session fixation »).
        session_regenerate_id(true);

        $_SESSION['admin_id']        = $user['id'];
        $_SESSION['admin_username']  = $user['username'];
        $_SESSION['admin_role']      = $user['role'];
        $_SESSION['admin_campus_id'] = $user['campus_id'] ?? null;
        $_SESSION['login_time']      = time();
        $_SESSION['last_activity']   = time();
        $_SESSION['admin_ua']        = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        unset($_SESSION['csrf_token']);

        $upd = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?');
        $upd->execute([$user['id']]);

        // Met à niveau le hachage si l'algorithme par défaut de PHP a évolué.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        securityClear('login_user', mb_strtolower($username));
        securityClear('login_ip', hitasClientIp());
        return true;
    }

    securityRecord('login_user', mb_strtolower($username));
    securityRecord('login_ip', hitasClientIp());
    hitasSecurityLog('échec de connexion admin pour « ' . oneLine($username, 40) . ' »');
    usleep(random_int(250000, 500000)); // ralentit les essais automatiques
    return false;
}

/* =========================================================
 * Rôles & campus (multi-campus)
 * ========================================================= */

/** Libellés lisibles des rôles disponibles. */
function adminRoleLabels(): array
{
    return [
        'super_admin' => 'Super-administrateur (accès total)',
        'redacteur'   => 'Rédacteur (Filières)',
        'boss'        => 'Direction (statistiques & synthèses)',
    ];
}

function isSuperAdmin(): bool
{
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

/** ID du campus de l'utilisateur connecté, ou null si accès à tous les campus (super-admin). */
function currentAdminCampusId(): ?int
{
    $val = $_SESSION['admin_campus_id'] ?? null;
    return $val !== null && $val !== '' ? (int)$val : null;
}

/**
 * Vérifie que l'utilisateur connecté a le droit de voir/gérer les données
 * du campus donné. Le super-admin a toujours accès à tout.
 */
function canAccessCampus(?int $campusId): bool
{
    if (isSuperAdmin()) {
        return true;
    }
    $userCampus = currentAdminCampusId();
    if ($userCampus === null) {
        // Utilisateur sans campus assigné : accès restreint (aucune donnée) par sécurité
        return false;
    }
    return $campusId !== null && $campusId === $userCampus;
}

/** Bloque l'accès si l'utilisateur n'a pas le droit de voir ce campus. */
function requireCampusAccess(?int $campusId): void
{
    requireLogin();
    if (!canAccessCampus($campusId)) {
        http_response_code(403);
        die('Accès refusé : cette donnée appartient à un autre campus.');
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Génère (ou réutilise) un jeton CSRF pour les formulaires. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Vérifie le jeton CSRF envoyé par un formulaire. */
function checkCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Requête invalide (jeton de sécurité expiré). Rechargez la page et réessayez.');
    }
}

/** Échappement rapide pour l'affichage HTML. */
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/** Génère un slug propre à partir d'un texte. */
function slugify(string $text): string
{
    // Conversion manuelle des caractères accentués (au lieu de iconv, peu
    // fiable selon la configuration Windows/WAMP — c'est ce qui causait des
    // erreurs comme "Génie Civil" -> "g-enie-civil" au lieu de "genie-civil").
    $map = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'æ' => 'ae',
        'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
        'Ç' => 'c',
        'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i',
        'Ñ' => 'n',
        'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o', 'Œ' => 'oe',
        'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u',
        'Ý' => 'y',
    ];
    $text = strtr($text, $map);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Rend un slug unique dans une table donnée, en ajoutant -2, -3... si besoin
 * (au lieu de planter avec une erreur de doublon en base). $excludeId permet
 * d'ignorer la ligne en cours lors d'une modification.
 */
function uniqueSlug(PDO $pdo, string $table, string $baseSlug, ?int $excludeId = null): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'item';
    $suffix = 2;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $suffix;
        $suffix++;
    }
}

/**
 * Gère l'upload d'une image et retourne le nom de fichier généré (ou null).
 * $subfolder ex: 'programs', 'news', etc.
 */
/**
 * Vérifie que le fichier téléversé EST bien ce que son extension prétend :
 * un vrai JPEG/PNG/WebP (lu par getimagesize), un vrai MP4/WebM (signature du
 * fichier), un vrai ICO. Un script ou une page HTML renommés en .jpg sont
 * refusés. $kind : 'image' | 'video' | 'favicon'.
 */
function uploadContentIsValid(string $tmpPath, string $ext, string $kind): bool
{
    if (!is_file($tmpPath) || filesize($tmpPath) === 0) {
        return false;
    }
    $ext = strtolower($ext);

    if ($kind === 'image') {
        if (filesize($tmpPath) > 30 * 1024 * 1024) {      // 30 Mo max par photo
            return false;
        }
        $info = @getimagesize($tmpPath);
        if (!$info) {
            return false;
        }
        $map = [IMAGETYPE_JPEG => ['jpg', 'jpeg'], IMAGETYPE_PNG => ['png'], IMAGETYPE_WEBP => ['webp'], IMAGETYPE_GIF => ['gif']];
        return isset($map[$info[2]]) && in_array($ext, $map[$info[2]], true);
    }

    $fh = @fopen($tmpPath, 'rb');
    if (!$fh) {
        return false;
    }
    $head = (string)fread($fh, 16);
    fclose($fh);

    if ($kind === 'video') {
        if ($ext === 'mp4') {
            return substr($head, 4, 4) === 'ftyp';                 // conteneur MP4/ISO
        }
        if ($ext === 'webm') {
            return substr($head, 0, 4) === "\x1A\x45\xDF\xA3";     // en-tête EBML (WebM)
        }
        return false;
    }

    if ($kind === 'favicon') {
        if ($ext === 'ico') {
            return substr($head, 0, 4) === "\x00\x00\x01\x00";
        }
        if ($ext === 'png') {
            $info = @getimagesize($tmpPath);
            return $info && $info[2] === IMAGETYPE_PNG;
        }
    }
    return false;
}

function handleImageUpload(string $inputName, string $subfolder): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        die('Format d\'image non autorisé. Utilisez : jpg, jpeg, png, webp.');
    }
    if (!is_uploaded_file($_FILES[$inputName]['tmp_name']) || !uploadContentIsValid($_FILES[$inputName]['tmp_name'], $ext, 'image')) {
        hitasSecurityLog('image refusée (contenu invalide) : ' . oneLine($_FILES[$inputName]['name'], 60));
        die('Ce fichier n\'est pas une image valide (ou dépasse 30 Mo).');
    }

    $filename = $subfolder . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir  = UPLOAD_PATH . $subfolder . '/';

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    move_uploaded_file($_FILES[$inputName]['tmp_name'], $destDir . $filename);

    return $filename;
}

/**
 * Gère l'upload d'une vidéo courte (utilisé par le diaporama du hero,
 * en alternative à une image) et retourne le nom de fichier généré (ou null).
 */
function handleVideoUpload(string $inputName, string $subfolder): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['mp4', 'webm'];
    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        die('Format vidéo non autorisé. Utilisez : mp4 ou webm.');
    }
    if (!is_uploaded_file($_FILES[$inputName]['tmp_name']) || !uploadContentIsValid($_FILES[$inputName]['tmp_name'], $ext, 'video')) {
        hitasSecurityLog('vidéo refusée (contenu invalide) : ' . oneLine($_FILES[$inputName]['name'], 60));
        die('Ce fichier n\'est pas une vidéo MP4 ou WebM valide.');
    }

    $filename = $subfolder . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir  = UPLOAD_PATH . $subfolder . '/';

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    move_uploaded_file($_FILES[$inputName]['tmp_name'], $destDir . $filename);

    return $filename;
}
