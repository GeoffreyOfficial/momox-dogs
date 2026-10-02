<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $message, string $errorKey = 'error'): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $errorKey, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, "Méthode non autorisée.");
}

/* -----------------------------------------------------------------
 * Détection d'un dépôt refusé par PHP lui-même AVANT que ce script ne
 * s'exécute (post_max_size dépassé dans le php.ini du serveur). Dans
 * ce cas précis, PHP vide entièrement $_POST et $_FILES sans poser
 * d'erreur exploitable : sans ce contrôle, la suite du script
 * répondrait à tort « mot de passe incorrect » (car $_POST est vide)
 * puis, si on passait ce filtre, « aucun fichier reçu » — deux
 * messages trompeurs qui ne disent pas le vrai problème. On le
 * détecte ici via CONTENT_LENGTH, qui lui reste toujours renseigné
 * par le serveur web même quand PHP a rejeté le corps de la requête.
 * ----------------------------------------------------------------- */
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
    fail(
        400,
        "Le fichier envoyé est trop volumineux pour la configuration actuelle du serveur (limite d'envoi PHP dépassée, indépendante de la limite de " . round(MAX_UPLOAD_SIZE / 1024 / 1024, 1) . " Mo de l'admin). Réduis la taille de la photo, ou demande à augmenter post_max_size / upload_max_filesize côté hébergement.",
        'server_upload_limit'
    );
}

/* -----------------------------------------------------------------
 * Revérification indépendante du mot de passe de publication, comme
 * pour save-content.php. La modale de confirmation dans admin.php
 * (via inc/verify-password.php) n'est qu'un confort d'interface : un
 * appel direct à ce endpoint sans le bon mot de passe ne doit pas
 * pouvoir écrire de fichier sur le serveur.
 * ----------------------------------------------------------------- */
$confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
    ? $_POST['confirm_password']
    : '';

$pwCheck = momox_verify_publish_password($confirmPassword);

if ($pwCheck['locked']) {
    fail(423, "Trop de tentatives — réessaie dans quelques secondes.", 'locked');
}
if (!$pwCheck['ok']) {
    fail(403, "Mot de passe incorrect. Réessaie.", 'bad_password');
}

if (empty($_FILES['photo'])) {
    fail(400, "Aucun fichier reçu.");
}

/* -----------------------------------------------------------------
 * Garde-fou : ce endpoint n'est prévu que pour un seul fichier par
 * appel (champ "photo" simple). Si jamais un appel envoyait plusieurs
 * fichiers sous ce même nom (champ "photo[]", ou appel direct mal
 * formé), $_FILES['photo']['error'] devient un tableau au lieu d'un
 * entier : sans ce contrôle, l'indexation de $messages plus bas avec
 * une clé de type tableau provoquerait une erreur fatale PHP au lieu
 * d'une réponse JSON propre.
 * ----------------------------------------------------------------- */
if (is_array($_FILES['photo']['error'] ?? null)) {
    fail(400, "Un seul fichier à la fois est accepté par cet envoi.", 'multiple_files');
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse la taille maximale autorisée par le serveur.",
        UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse la taille maximale autorisée.",
        UPLOAD_ERR_PARTIAL    => "L'envoi a été interrompu, réessaie.",
        UPLOAD_ERR_NO_FILE    => "Aucun fichier reçu.",
    ];
    fail(400, $messages[$file['error']] ?? "Erreur d'envoi (code {$file['error']}).");
}

if ($file['size'] > MAX_UPLOAD_SIZE) {
    $maxMo = round(MAX_UPLOAD_SIZE / 1024 / 1024, 1);
    fail(400, "Ce fichier dépasse la taille maximale autorisée ({$maxMo} Mo).");
}

$originalName = $file['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
    fail(400, "Format non autorisé (" . htmlspecialchars($ext) . "). Utilise .jpg, .jpeg, .png, .webp ou .gif.", 'bad_extension');
}

$contentError = momox_verify_image_content($file['tmp_name'], $ext);
if ($contentError !== null) {
    fail(400, $contentError, 'invalid_image');
}

/* -----------------------------------------------------------------
 * Deux modes :
 *
 * 1) target_path fourni (ex. "images/galerie/chien-assis.jpg") : le
 *    fichier DOIT être enregistré exactement sous ce nom, quel que
 *    soit le nom du fichier choisi par l'utilisateur sur son
 *    ordinateur. C'est le mode utilisé quand l'admin comble une photo
 *    précisément attendue par content.json avant publication.
 *
 * 2) sinon : comportement historique, le nom du fichier envoyé est
 *    nettoyé et conservé tel quel (panneau d'envoi libre).
 * ----------------------------------------------------------------- */
$targetPath = isset($_POST['target_path']) ? trim((string) $_POST['target_path']) : '';

if ($targetPath !== '') {
    $validPath = momox_validate_relative_image_path($targetPath);
    if ($validPath === null) {
        fail(400, "Chemin cible invalide pour cette photo.", 'bad_target');
    }
    $targetExt = strtolower(pathinfo($validPath, PATHINFO_EXTENSION));
    if ($targetExt !== $ext) {
        fail(
            400,
            "Le fichier envoyé est un .{$ext} mais l'emplacement attendu se termine par .{$targetExt}. Corrige le champ dans l'admin ou envoie un fichier .{$targetExt}.",
            'extension_mismatch'
        );
    }

    $destPath = SITE_ROOT . '/' . $validPath;
    $destDir  = dirname($destPath);
    $safeName = basename($validPath);

    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            fail(500, "Le dossier de destination n'existe pas et n'a pas pu être créé.");
        }
    }
    if (!is_writable($destDir)) {
        fail(500, "Le dossier de destination n'est pas accessible en écriture sur le serveur (droits/permissions OVH).");
    }

    // Sécurité anti-collision de casse (Chien.jpg vs chien.jpg) : le
    // serveur OVH étant sensible à la casse, on vérifie explicitement
    // qu'aucun fichier au nom quasi identique n'existe déjà avant
    // d'écrire, pour ne jamais écraser silencieusement une photo.
    $existing = @scandir($destDir) ?: [];
    foreach ($existing as $existingFile) {
        if (strcasecmp($existingFile, $safeName) === 0 && $existingFile !== $safeName) {
            fail(
                409,
                "Un fichier au nom presque identique existe déjà sur le serveur : « {$existingFile} » (casse différente de « {$safeName} »). Corrige le nom exact dans l'admin plutôt que d'en créer un second.",
                'already_exists'
            );
        }
    }
    if (file_exists($destPath)) {
        fail(409, "« {$safeName} » existe déjà à cet emplacement — rien n'a été écrasé.", 'already_exists');
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        fail(500, "Échec de l'enregistrement du fichier sur le serveur.");
    }
    @chmod($destPath, 0644);

    echo json_encode(['ok' => true, 'path' => $validPath, 'filename' => $safeName], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------- Mode 2 : envoi libre (comportement historique) -------------------- */

/* Dossier de destination choisi dans le panneau « Envoyer des photos » :
 * 'galerie' → images/galerie/, sinon (ou valeur absente/inconnue) →
 * images/ comme avant. Comportement historique inchangé par défaut. */
$bucket = isset($_POST['bucket']) && $_POST['bucket'] === 'galerie' ? 'galerie' : 'images';
$uploadDir = $bucket === 'galerie' ? (IMAGES_DIR . '/galerie') : IMAGES_DIR;
$bucketLabel = $bucket === 'galerie' ? 'images/galerie/' : 'images/';

$baseName = pathinfo($originalName, PATHINFO_FILENAME);
if (function_exists('iconv')) {
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $baseName);
    if ($translit !== false) $baseName = $translit;
}
$baseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $baseName);
$baseName = trim($baseName, '-_.');
if ($baseName === '') $baseName = 'photo';

$safeName = $baseName . '.' . $ext;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        fail(500, "Le dossier {$bucketLabel} n'existe pas et n'a pas pu être créé.");
    }
}

$destPath = $uploadDir . '/' . $safeName;

$existing = @scandir($uploadDir) ?: [];
foreach ($existing as $existingFile) {
    if (strcasecmp($existingFile, $safeName) === 0) {
        fail(
            409,
            "Une photo nommée « {$safeName} » existe déjà dans {$bucketLabel}. Renomme ton fichier (ex. {$baseName}-2.{$ext}) ou supprime d'abord l'ancienne photo si tu veux la remplacer.",
            'already_exists'
        );
    }
}

if (!is_writable($uploadDir)) {
    fail(500, "Le dossier {$bucketLabel} n'est pas accessible en écriture sur le serveur (droits/permissions OVH).");
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    fail(500, "Échec de l'enregistrement du fichier sur le serveur.");
}

@chmod($destPath, 0644);

echo json_encode([
    'ok' => true,
    'filename' => $safeName,
    'bucket' => $bucket,
    'path' => $bucketLabel . $safeName,
    'renamed' => ($safeName !== $originalName),
], JSON_UNESCAPED_UNICODE);
