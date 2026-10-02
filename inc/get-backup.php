<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_gb(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = isset($_GET['file']) ? (string) $_GET['file'] : '';

// Nom de fichier strictement validé (empêche toute tentative de sortir
// du dossier content/ ou de lire un autre fichier du serveur).
$pattern = '/^content-\d{4}-\d{2}-\d{2}_\d{2}h\d{2}m\d{2}s(-\d+)?\.json$/';
if (!preg_match($pattern, $file)) {
    fail_gb(400, "Nom de fichier de sauvegarde invalide.");
}

$path = BACKUP_DIR . '/' . $file;
if (!is_file($path)) {
    fail_gb(404, "Cette ancienne version est introuvable sur le serveur.");
}

$raw = file_get_contents($path);
if ($raw === false) {
    fail_gb(500, "Impossible de lire ce fichier sur le serveur.");
}

$data = json_decode($raw, true);
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    fail_gb(500, "Ce fichier de sauvegarde est corrompu (JSON invalide).");
}
$shapeError = momox_validate_content_shape($data);
if ($shapeError !== null) {
    fail_gb(500, "Cette sauvegarde a une structure inattendue et ne peut pas être rechargée en sécurité : " . $shapeError);
}

echo json_encode(['ok' => true, 'file' => $file, 'content' => $data], JSON_UNESCAPED_UNICODE);
