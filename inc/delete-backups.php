<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_db(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail_db(405, "Méthode non autorisée.");
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || !isset($body['files']) || !is_array($body['files']) || !count($body['files'])) {
    fail_db(400, "Requête invalide : aucune version à supprimer n'a été transmise.");
}

// Même validation stricte que list-backups.php / get-backup.php : empêche
// toute tentative de sortir du dossier des sauvegardes.
$pattern = '/^content-\d{4}-\d{2}-\d{2}_\d{2}h\d{2}m\d{2}s(-\d+)?\.json$/';

// Même verrou que save-content.php / delete-images.php : évite qu'une
// publication en cours ne crée une sauvegarde pendant qu'on est en
// train d'en supprimer une (ou l'inverse), pour rester cohérent avec
// le reste des opérations d'écriture sur le dossier content/.
$lockPath = SITE_ROOT . '/.save-content.lock';
$lockHandle = @fopen($lockPath, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
    fail_db(500, "Le serveur est occupé par une autre opération — réessaie dans quelques secondes.");
}

$deleted = [];
$skipped = [];

foreach ($body['files'] as $file) {
    $file = is_string($file) ? $file : '';
    if (!preg_match($pattern, $file)) {
        $skipped[] = ['file' => $file, 'reason' => 'Nom de fichier invalide.'];
        continue;
    }
    $path = BACKUP_DIR . '/' . $file;
    if (!is_file($path)) {
        $skipped[] = ['file' => $file, 'reason' => 'Déjà supprimée sur le serveur.'];
        continue;
    }
    if (@unlink($path)) {
        $deleted[] = $file;
    } else {
        $skipped[] = ['file' => $file, 'reason' => 'Échec de la suppression sur le serveur.'];
    }
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

echo json_encode(['ok' => true, 'deleted' => $deleted, 'skipped' => $skipped], JSON_UNESCAPED_UNICODE);
