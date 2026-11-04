<?php
// =========================================================
// Sécurité transversale HITAS — chargé automatiquement par
// config/database.php, donc actif sur TOUTES les pages (site public et admin).
//
//  - en-têtes HTTP de sécurité (anti-clickjacking, nosniff, HSTS, CSP de base…)
//  - redirection HTTP -> HTTPS dès que le site est hébergé en ligne
//  - cookies de session durcis (HttpOnly, SameSite, Secure)
//  - erreurs PHP jamais affichées aux visiteurs en ligne (journalisées à la place)
//  - limitation de débit (anti brute-force / anti-spam) et utilitaires de formulaires
//
// En local (localhost, 127.0.0.1, *.test, *.localhost) rien ne bloque le travail :
// pas de redirection HTTPS, erreurs affichées.
// =========================================================

if (defined('HITAS_SECURITY_LOADED')) {
    return;
}
define('HITAS_SECURITY_LOADED', true);

/** Vrai quand le site tourne sur la machine du développeur (pas en ligne). */
function hitasIsLocal(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));
    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]' || $host === '::1') {
        return true;
    }
    return (bool)preg_match('/\.(localhost|test|local|invalid)$/', $host);
}

/** Vrai si la requête arrive en HTTPS (y compris derrière un proxy / Cloudflare). */
function hitasIsHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    if (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos((string)$_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false) {
        return true;
    }
    return false;
}

/** Adresse IP du visiteur. Les en-têtes de proxy ne sont crus que si la config locale l'autorise. */
function hitasClientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (defined('HITAS_TRUSTED_PROXY_HEADER') && HITAS_TRUSTED_PROXY_HEADER) {
        $h = 'HTTP_' . strtoupper(str_replace('-', '_', HITAS_TRUSTED_PROXY_HEADER));
        if (!empty($_SERVER[$h])) {
            $candidate = trim(explode(',', (string)$_SERVER[$h])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
            }
        }
    }
    return $ip;
}

/** Secret propre au site (généré une fois, stocké dans config/ qui est interdit d'accès web). */
function hitasSecret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    $file = __DIR__ . '/../config/secret.key';
    if (is_file($file)) {
        $secret = trim((string)@file_get_contents($file));
    }
    if (!$secret || strlen($secret) < 32) {
        $secret = bin2hex(random_bytes(32));
        @file_put_contents($file, $secret, LOCK_EX);
    }
    return $secret;
}

/** Journal d'événements de sécurité (fichier d'erreurs PHP). */
function hitasSecurityLog(string $message): void
{
    error_log('[HITAS sécurité] ' . $message . ' | ip=' . hitasClientIp());
}

if (PHP_SAPI !== 'cli') {
    $hitasLocal = hitasIsLocal();
    $hitasHttps = hitasIsHttps();

    /* ---- Erreurs : jamais affichées au public en ligne ------------------- */
    if (!$hitasLocal) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
            @file_put_contents($logDir . '/.htaccess', "Require all denied\n");
        }
        if (is_dir($logDir) && is_writable($logDir)) {
            ini_set('error_log', $logDir . '/php-error.log');
        }
        // Une exception non rattrapée n'affiche jamais de détails techniques au public :
        // page neutre pour le visiteur, détail dans le journal.
        set_exception_handler(static function (Throwable $e): void {
            error_log('[HITAS erreur] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
            }
            echo '<!DOCTYPE html><html lang="fr"><meta charset="UTF-8"><title>Erreur — HITAS</title>'
                . '<body style="font-family:sans-serif;max-width:520px;margin:12vh auto;text-align:center;color:#33414f">'
                . '<h1 style="color:#1B6FBF">Une erreur est survenue</h1>'
                . '<p>Nous sommes désolés : la page demandée est momentanément indisponible. Merci de réessayer dans quelques instants.</p>'
                . '<p><a href="/" style="color:#9c7c3c;font-weight:600">Retour à l\'accueil</a></p></body></html>';
        });
    }

    /* ---- Redirection HTTPS (en ligne uniquement) ------------------------- */
    // Désactivable depuis config/config.local.php : define('HITAS_FORCE_HTTPS', false);
    $forceHttps = defined('HITAS_FORCE_HTTPS') ? (bool)HITAS_FORCE_HTTPS : !$hitasLocal;
    if ($forceHttps && !$hitasHttps && !headers_sent() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '' && preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
            header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
    }

    /* ---- Cookies de session durcis --------------------------------------- */
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        session_name('HITASSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $hitasHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /* ---- En-têtes HTTP de sécurité --------------------------------------- */
    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        // CSP volontairement « de base » : le site utilise des scripts/styles en
        // ligne et Google Traduction, donc on ne restreint pas script-src, mais
        // on interdit objets/plugins, détournement de <base>, envoi de formulaires
        // vers un autre site et affichage du site dans le cadre d'un autre site.
        $csp = "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
        if ($hitasHttps) {
            $csp .= '; upgrade-insecure-requests';
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header('Content-Security-Policy: ' . $csp);

        // L'administration ne doit jamais être mise en cache ni indexée.
        if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }
    }
}

/* =========================================================
 * Limitation de débit (anti brute-force / anti-spam)
 * Table security_events, créée automatiquement au premier usage.
 * ========================================================= */

function securityEnsureTable(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS security_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kind VARCHAR(40) NOT NULL,
        key_hash CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_kind_key_time (kind, key_hash, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $done = true;
    // Nettoyage occasionnel des événements de plus de 2 jours.
    if (random_int(1, 50) === 1) {
        $pdo->exec('DELETE FROM security_events WHERE created_at < (NOW() - INTERVAL 2 DAY)');
    }
}

function securityKeyHash(string $key): string
{
    return hash_hmac('sha256', strtolower($key), hitasSecret());
}

/** Enregistre un événement (ex : échec de connexion, envoi de formulaire). */
function securityRecord(string $kind, string $key): void
{
    try {
        $pdo = getDB();
        securityEnsureTable($pdo);
        $pdo->prepare('INSERT INTO security_events (kind, key_hash) VALUES (?, ?)')->execute([$kind, securityKeyHash($key)]);
    } catch (Throwable $e) {
        error_log('securityRecord: ' . $e->getMessage());
    }
}

/** Nombre d'événements de ce type pour cette clé dans les $windowSec dernières secondes. */
function securityCount(string $kind, string $key, int $windowSec): int
{
    try {
        $pdo = getDB();
        securityEnsureTable($pdo);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM security_events WHERE kind = ? AND key_hash = ? AND created_at > (NOW() - INTERVAL ' . (int)$windowSec . ' SECOND)');
        $stmt->execute([$kind, securityKeyHash($key)]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('securityCount: ' . $e->getMessage());
        return 0;
    }
}

function securityClear(string $kind, string $key): void
{
    try {
        $pdo = getDB();
        securityEnsureTable($pdo);
        $pdo->prepare('DELETE FROM security_events WHERE kind = ? AND key_hash = ?')->execute([$kind, securityKeyHash($key)]);
    } catch (Throwable $e) {
        error_log('securityClear: ' . $e->getMessage());
    }
}

/* =========================================================
 * Formulaires publics (anti-spam, sans CAPTCHA pour le visiteur)
 * ========================================================= */

/** Champs cachés à placer dans un formulaire public : piège à robots + jeton horodaté signé. */
function publicFormFields(string $formKey): string
{
    $t = time();
    $sig = hash_hmac('sha256', $formKey . '|' . $t, hitasSecret());
    return '<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">'
        . '<label>Ne pas remplir ce champ <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="form_ts" value="' . $t . '">'
        . '<input type="hidden" name="form_sig" value="' . $sig . '">';
}

/**
 * Contrôle d'un envoi de formulaire public. Retourne null si tout va bien, ou un
 * message d'erreur à afficher. Un robot qui remplit le piège reçoit un faux
 * « succès » côté appelant (voir $silent) pour ne pas être informé.
 *
 * @param bool $silent mis à true si l'envoi doit être ignoré en silence (robot)
 */
function publicFormCheck(string $formKey, int $maxPerHour, bool &$silent): ?string
{
    $silent = false;

    if (!empty($_POST['website_url'])) {            // piège à robots rempli
        hitasSecurityLog("honeypot rempli ($formKey)");
        $silent = true;
        return null;
    }

    $ts  = (int)($_POST['form_ts'] ?? 0);
    $sig = (string)($_POST['form_sig'] ?? '');
    $expected = hash_hmac('sha256', $formKey . '|' . $ts, hitasSecret());
    if ($ts <= 0 || !hash_equals($expected, $sig)) {
        return 'Le formulaire a expiré ou est invalide. Rechargez la page puis réessayez.';
    }
    $age = time() - $ts;
    if ($age < 3) {                                  // rempli en moins de 3 secondes : robot
        hitasSecurityLog("envoi trop rapide ($formKey)");
        $silent = true;
        return null;
    }
    if ($age > 6 * 3600) {                           // formulaire resté ouvert plus de 6 h
        return 'Le formulaire a expiré. Rechargez la page puis réessayez.';
    }

    $ipKey = hitasClientIp();
    if (securityCount('form_' . $formKey, $ipKey, 3600) >= $maxPerHour) {
        hitasSecurityLog("limite de débit atteinte ($formKey)");
        return 'Vous avez envoyé trop de messages en peu de temps. Merci de réessayer dans une heure.';
    }
    securityRecord('form_' . $formKey, $ipKey);
    return null;
}

/** Retire retours à la ligne et caractères de contrôle (en-têtes d'e-mail, sujets…). */
function oneLine(string $text, int $maxLen = 200): string
{
    $text = preg_replace('/[\r\n\t\x00-\x1F\x7F]+/', ' ', $text);
    return mb_substr(trim($text), 0, $maxLen);
}

/* =========================================================
 * HTML riche saisi en admin : nettoyage à l'affichage
 * (liste blanche de balises — un compte admin compromis ne peut plus
 * injecter de script chez les visiteurs).
 * ========================================================= */
function sanitizeRichHtml(?string $html): string
{
    $html = (string)$html;
    if (trim($html) === '') {
        return '';
    }
    $allowedTags = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'small' => [], 'sup' => [], 'sub' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'span' => ['translate', 'class'], 'div' => ['class'],
        'a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'table' => ['class'], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    if (!class_exists('DOMDocument')) {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="hitas-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $safeUrl = static function (string $url, bool $isImg): bool {
        $url = trim(preg_replace('/[\x00-\x20]+/', '', $url));
        if ($url === '') {
            return false;
        }
        if (preg_match('~^(https?:|mailto:|tel:|/|#|\?)~i', $url)) {
            return true;
        }
        // chemin relatif sans schéma (ex : uploads/x.jpg) : refusé s'il contient « : » avant tout « / »
        return !preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url);
    };

    $clean = static function (DOMNode $node) use (&$clean, $allowedTags, $safeUrl): void {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (!isset($allowedTags[$tag])) {
                // balises dangereuses (script, style, iframe, object, form…) : supprimées avec leur contenu ;
                // les autres balises inconnues sont « dépliées » (on garde le texte).
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript'], true)) {
                    $node->removeChild($child);
                } else {
                    $clean($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (!in_array($name, $allowedTags[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                    continue;
                }
                if (($name === 'href' || $name === 'src') && !$safeUrl($attr->nodeValue, $name === 'src')) {
                    $child->removeAttribute($attr->nodeName);
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            $clean($child);
        }
    };

    $root = $doc->getElementById('hitas-root');
    if (!$root) {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
    $clean($root);
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}
