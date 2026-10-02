<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_di(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail_di(405, "Méthode non autorisée.");
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || !isset($body['paths']) || !is_array($body['paths']) || !count($body['paths'])) {
    fail_di(400, "Aucune photo à supprimer n'a été précisée.");
}

/* Re-vérification côté serveur, indépendante de ce que pense le
 * navigateur : on ne supprime JAMAIS une photo encore référencée dans
 * le content.json actuellement en ligne, même si la liste envoyée la
 * contient (protection contre un état obsolète côté client). */
$liveContent = [];
if (is_file(CONTENT_FILE)) {
    $liveRaw = file_get_contents(CONTENT_FILE);
    $decoded = json_decode($liveRaw, true);
    if (is_array($decoded)) $liveContent = $decoded;
}
$usedPaths = array_flip(momox_collect_image_paths($liveContent));

// Même verrou que save-content.php : évite qu'une publication en
// cours crée une sauvegarde pendant qu'on est en train d'en supprimer,
// ou l'inverse.
$lockPath = SITE_ROOT . '/.save-content.lock';
$lockHandle = @fopen($lockPath, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
    fail_di(500, "Le serveur est occupé par une autre opération — réessaie dans quelques secondes.");
}

$deletedPhotos = [];
$skipped = [];

foreach ($body['paths'] as $requested) {
    $requested = is_string($requested) ? $requested : '';
    $valid = momox_validate_relative_image_path($requested);

    if ($valid === null) {
        $skipped[] = ['path' => $requested, 'reason' => "Chemin invalide."];
        continue;
    }
    if (isset($usedPaths[$valid])) {
        $skipped[] = ['path' => $valid, 'reason' => "Encore référencée dans le contenu actuellement en ligne — non supprimée par sécurité."];
        continue;
    }
    $abs = SITE_ROOT . '/' . $valid;
    if (!is_file($abs)) {
        $skipped[] = ['path' => $valid, 'reason' => "Fichier déjà absent du serveur."];
        continue;
    }
    if (@unlink($abs)) {
        $deletedPhotos[] = $valid;
    } else {
        $skipped[] = ['path' => $valid, 'reason' => "Échec de la suppression sur le serveur (droits/permissions OVH)."];
    }
}

/* Nettoyage des anciennes sauvegardes devenues incohérentes : toute
 * sauvegarde qui référence au moins une des photos supprimées est
 * elle-même supprimée, plutôt que de laisser un point de restauration
 * qui pointerait vers une photo qui n'existe plus. */
$deletedBackups = [];
if ($deletedPhotos && is_dir(BACKUP_DIR)) {
    $pattern = '/^content-\d{4}-\d{2}-\d{2}_\d{2}h\d{2}m\d{2}s(-\d+)?\.json$/';
    foreach (scandir(BACKUP_DIR) as $f) {
        if (!preg_match($pattern, $f)) continue;
        $path = BACKUP_DIR . '/' . $f;
        if (!is_file($path)) continue;
        $raw2 = @file_get_contents($path);
        $data = $raw2 !== false ? json_decode($raw2, true) : null;
        if (!is_array($data)) continue;
        $paths = momox_collect_image_paths($data);
        if (array_intersect($paths, $deletedPhotos)) {
            if (@unlink($path)) $deletedBackups[] = $f;
        }
    }
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

echo json_encode([
    'ok' => true,
    'deletedPhotos' => $deletedPhotos,
    'deletedBackups' => $deletedBackups,
    'skipped' => $skipped,
], JSON_UNESCAPED_UNICODE);
