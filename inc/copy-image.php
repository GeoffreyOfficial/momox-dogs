<?php
/**
 * Copie une photo déjà présente sur le serveur d'un dossier vers
 * l'autre (images/ -> images/galerie/, ou l'inverse), pour la modale
 * « Choisir une image déjà sur le serveur » de admin.php quand la
 * photo choisie ne vient pas du bon dossier pour le champ concerné.
 *
 * Le fichier d'origine n'est jamais supprimé : après cette opération,
 * la photo existe dans les DEUX dossiers.
 *
 * Comme pour save-content.php et upload-image.php, le mot de passe
 * admin est revérifié ici indépendamment de la session déjà ouverte —
 * la modale de confirmation dans admin.php n'est qu'un confort
 * d'interface, ce endpoint reste le seul rempart réellement fiable.
 */
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_ci2(int $code, string $message, string $errorKey = 'error'): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $errorKey, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail_ci2(405, "Méthode non autorisée.");
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    fail_ci2(400, "Requête invalide (JSON illisible).");
}

$confirmPassword = isset($body['confirm_password']) && is_string($body['confirm_password'])
    ? $body['confirm_password']
    : '';

$pwCheck = momox_verify_publish_password($confirmPassword);

if ($pwCheck['locked']) {
    fail_ci2(423, "Trop de tentatives — réessaie dans quelques secondes.", 'locked');
}
if (!$pwCheck['ok']) {
    fail_ci2(403, "Mot de passe incorrect. Réessaie.", 'bad_password');
}

$sourceRaw = isset($body['source']) && is_string($body['source']) ? $body['source'] : '';
$targetBucket = isset($body['target_bucket']) && is_string($body['target_bucket']) ? $body['target_bucket'] : '';

if (!in_array($targetBucket, ['images', 'galerie'], true)) {
    fail_ci2(400, "Dossier de destination invalide.", 'bad_target');
}

$sourcePath = momox_validate_relative_image_path($sourceRaw);
if ($sourcePath === null) {
    fail_ci2(400, "Chemin de photo source invalide.", 'bad_source');
}

$sourceAbs = SITE_ROOT . '/' . $sourcePath;
if (!is_file($sourceAbs)) {
    fail_ci2(404, "Cette photo n'existe plus sur le serveur — recharge la liste et réessaie.", 'not_found');
}

$sourceBucket = (strpos($sourcePath, 'images/galerie/') === 0) ? 'galerie' : 'images';
if ($sourceBucket === $targetBucket) {
    // Rien à faire : déjà dans le bon dossier — on renvoie simplement
    // le chemin tel quel (ne devrait normalement pas arriver, le
    // front-end évite déjà cet appel dans ce cas).
    echo json_encode(['ok' => true, 'path' => $sourcePath], JSON_UNESCAPED_UNICODE);
    exit;
}

$fileName = basename($sourcePath);
$destDir = ($targetBucket === 'galerie') ? (IMAGES_DIR . '/galerie') : IMAGES_DIR;
$destRelPrefix = ($targetBucket === 'galerie') ? 'images/galerie/' : 'images/';

if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true)) {
        fail_ci2(500, "Le dossier de destination n'existe pas et n'a pas pu être créé.");
    }
}
if (!is_writable($destDir)) {
    fail_ci2(500, "Le dossier de destination n'est pas accessible en écriture sur le serveur (droits/permissions OVH).");
}

// Même sécurité anti-collision de casse que upload-image.php (serveur
// OVH sensible à la casse) : on vérifie qu'aucun fichier au nom quasi
// identique n'existe déjà avant d'écrire, pour ne jamais écraser
// silencieusement une autre photo.
$existing = @scandir($destDir) ?: [];
foreach ($existing as $existingFile) {
    if (strcasecmp($existingFile, $fileName) === 0 && $existingFile !== $fileName) {
        fail_ci2(
            409,
            "Un fichier au nom presque identique existe déjà dans le dossier de destination : « {$existingFile} » (casse différente de « {$fileName} »).",
            'already_exists'
        );
    }
}

$destPath = $destDir . '/' . $fileName;
$destRelPath = $destRelPrefix . $fileName;

if (file_exists($destPath)) {
    // Le fichier est déjà présent à destination (copie précédente,
    // ou même nom coïncidant) : on considère l'opération faite plutôt
    // que d'écraser un fichier existant sans certitude que c'est bien
    // la même photo.
    echo json_encode(['ok' => true, 'path' => $destRelPath], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!@copy($sourceAbs, $destPath)) {
    fail_ci2(500, "Échec de la copie du fichier sur le serveur.");
}
@chmod($destPath, 0644);

echo json_encode(['ok' => true, 'path' => $destRelPath], JSON_UNESCAPED_UNICODE);
