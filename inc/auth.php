<?php
/**
 * Authentification de l'admin. Inclus par admin.php et par chaque
 * script de l'API (save-content.php, upload-image.php).
 */
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Cookie de session sécurisé (https only quand disponible, non
    // accessible en JS) pour éviter qu'une session ne soit volée.
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 30 minutes d'inactivité avant expiration automatique de la session.
const MOMOX_SESSION_IDLE_SECONDS = 30 * 60;

function momox_is_logged_in(): bool {
    if (empty($_SESSION['momox_admin_ok'])) {
        return false;
    }

    // Expiration par inactivité : au-delà de MOMOX_SESSION_IDLE_SECONDS
    // sans requête authentifiée, la session est fermée d'elle-même —
    // utile sur un appareil partagé ou un onglet oublié ouvert.
    $lastActivity = (int)($_SESSION['momox_last_activity'] ?? 0);
    if ($lastActivity !== 0 && (time() - $lastActivity) > MOMOX_SESSION_IDLE_SECONDS) {
        $_SESSION = [];
        session_destroy();
        return false;
    }

    $_SESSION['momox_last_activity'] = time();
    return true;
}

/**
 * Anti-brute-force sur le login lui-même, sur le même principe que
 * momox_verify_publish_password() ci-dessous : verrou temporaire posé
 * en session après plusieurs échecs. Même limite assumée qu'à la
 * publication (verrou par session, pas par IP, peu fiable derrière un
 * proxy/CDN) — mais ça bloque déjà les essais automatisés basiques
 * qui ne rejouent pas le cookie de session.
 *
 * Retourne ['ok' => bool, 'locked' => bool, 'retry_after' => int|null]
 */
const MOMOX_LOGIN_MAX_ATTEMPTS = 5;
const MOMOX_LOGIN_LOCKOUT_SECONDS = 30;

function momox_login(string $password): array {
    $now = time();
    $lockedUntil = (int)($_SESSION['momox_login_locked_until'] ?? 0);

    if ($lockedUntil > $now) {
        return ['ok' => false, 'locked' => true, 'retry_after' => $lockedUntil - $now];
    }

    if (hash_equals(ADMIN_PASSWORD, $password)) {
        unset($_SESSION['momox_login_fail_count'], $_SESSION['momox_login_locked_until']);
        session_regenerate_id(true);
        $_SESSION['momox_admin_ok'] = true;
        $_SESSION['momox_last_activity'] = $now;
        return ['ok' => true, 'locked' => false, 'retry_after' => null];
    }

    $fails = (int)($_SESSION['momox_login_fail_count'] ?? 0) + 1;

    if ($fails >= MOMOX_LOGIN_MAX_ATTEMPTS) {
        $_SESSION['momox_login_fail_count'] = 0;
        $_SESSION['momox_login_locked_until'] = $now + MOMOX_LOGIN_LOCKOUT_SECONDS;
        return ['ok' => false, 'locked' => true, 'retry_after' => MOMOX_LOGIN_LOCKOUT_SECONDS];
    }

    $_SESSION['momox_login_fail_count'] = $fails;
    return ['ok' => false, 'locked' => false, 'retry_after' => null];
}

function momox_logout(): void {
    $_SESSION = [];
    session_destroy();
}

/**
 * Re-confirmation du mot de passe avant publication.
 *
 * Indépendante de la session déjà ouverte (momox_is_logged_in) : on
 * redemande volontairement le mot de passe juste avant chaque
 * publication, pour éviter qu'une session laissée ouverte (onglet
 * oublié, appareil partagé…) ne suffise à modifier le site en ligne.
 *
 * Anti-brute-force : après MOMOX_PUBLISH_MAX_ATTEMPTS échecs, un
 * verrou temporaire de MOMOX_PUBLISH_LOCKOUT_SECONDS est posé en
 * session — indépendant de l'IP (peu fiable derrière un proxy/CDN) et
 * suffisant ici puisqu'il faut de toute façon déjà être connecté.
 *
 * Retourne toujours un tableau :
 *   ['ok' => bool, 'locked' => bool, 'retry_after' => int|null]
 */
const MOMOX_PUBLISH_MAX_ATTEMPTS = 5;
const MOMOX_PUBLISH_LOCKOUT_SECONDS = 30;

function momox_verify_publish_password(string $password): array {
    $now = time();
    $lockedUntil = (int)($_SESSION['momox_publish_locked_until'] ?? 0);

    if ($lockedUntil > $now) {
        return ['ok' => false, 'locked' => true, 'retry_after' => $lockedUntil - $now];
    }

    if ($password !== '' && hash_equals(ADMIN_PASSWORD, $password)) {
        unset($_SESSION['momox_publish_fail_count'], $_SESSION['momox_publish_locked_until']);
        return ['ok' => true, 'locked' => false, 'retry_after' => null];
    }

    $fails = (int)($_SESSION['momox_publish_fail_count'] ?? 0) + 1;

    if ($fails >= MOMOX_PUBLISH_MAX_ATTEMPTS) {
        $_SESSION['momox_publish_fail_count'] = 0;
        $_SESSION['momox_publish_locked_until'] = $now + MOMOX_PUBLISH_LOCKOUT_SECONDS;
        return ['ok' => false, 'locked' => true, 'retry_after' => MOMOX_PUBLISH_LOCKOUT_SECONDS];
    }

    $_SESSION['momox_publish_fail_count'] = $fails;
    return ['ok' => false, 'locked' => false, 'retry_after' => null];
}

/**
 * À appeler en tout début des scripts de l'API (save-content.php,
 * upload-image.php). Coupe la requête avec une réponse JSON 401 si la
 * personne n'est pas connectée à l'admin — c'est ce qui garantit que
 * le mot de passe est nécessaire pour publier ou envoyer une photo,
 * même si quelqu'un devine l'adresse de ces fichiers.
 */
function momox_require_login_api(): void {
    if (!momox_is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'not_logged_in',
            'message' => "Session expirée ou non connectée. Recharge la page admin.php et reconnecte-toi avec le mot de passe.",
        ]);
        exit;
    }
}
