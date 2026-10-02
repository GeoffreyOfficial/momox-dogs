<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $message, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => false, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, "Méthode non autorisée.");
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    fail(400, "Aucune donnée reçue.");
}

$envelope = json_decode($raw, true);
if ($envelope === null && json_last_error() !== JSON_ERROR_NONE) {
    fail(400, "Le contenu envoyé n'est pas un JSON valide : " . json_last_error_msg());
}

// La requête doit contenir le futur content.json ET le mot de passe
// re-saisi juste avant publication — voir momox_verify_publish_password()
// dans auth.php. C'est cette vérification, indépendante de la session
// déjà ouverte, qui garantit qu'un mot de passe est bien exigé à
// chaque publication, même si quelqu'un contourne la modale côté JS.
if (!is_array($envelope) || !array_key_exists('content', $envelope) || !array_key_exists('confirm_password', $envelope)) {
    fail(400, "Requête de publication incomplète (mot de passe de confirmation manquant). Recharge la page admin.php et réessaie.");
}

$confirmPassword = is_string($envelope['confirm_password']) ? $envelope['confirm_password'] : '';
$pwCheck = momox_verify_publish_password($confirmPassword);

if ($pwCheck['locked']) {
    fail(423, "Trop de tentatives de mot de passe — réessaie dans quelques secondes.", [
        'locked' => true,
        'retry_after' => $pwCheck['retry_after'],
    ]);
}
if (!$pwCheck['ok']) {
    fail(403, "Mot de passe de confirmation incorrect — publication refusée. Rien n'a été modifié sur le serveur.");
}

$data = $envelope['content'];

// Dernier rempart avant écriture : le JSON est syntaxiquement valide,
// mais ressemble-t-il vraiment à un content.json complet ? Sans ce
// contrôle, un envoi vide/partiel/mal formé pourrait écraser tout le
// site avec un fichier inutilisable.
$shapeError = momox_validate_content_shape($data);
if ($shapeError !== null) {
    fail(400, $shapeError . " Publication refusée par sécurité : rien n'a été modifié sur le serveur.");
}

// Re-sérialisation propre et lisible (identique au format du bouton
// "Télécharger content.json").
$pretty = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($pretty === false || trim($pretty) === '') {
    fail(500, "Impossible de reformater le JSON avant enregistrement — rien n'a été modifié sur le serveur.");
}
// Re-décodage de contrôle : garantit que ce qui sera écrit sur le
// disque est bien relisible et de la bonne forme, avant d'y toucher.
$roundTrip = json_decode($pretty, true);
if ($roundTrip === null || momox_validate_content_shape($roundTrip) !== null) {
    fail(500, "Anomalie interne lors de la préparation du fichier — rien n'a été modifié sur le serveur par précaution.");
}

if (!is_writable(SITE_ROOT)) {
    fail(500, "Le dossier du site n'est pas accessible en écriture sur le serveur (droits/permissions OVH).");
}

// Verrou de fichier : si deux publications arrivent quasi en même
// temps (deux onglets, deux appareils), on les sérialise strictement
// plutôt que de risquer une sauvegarde écrasée ou un entrelacement
// d'écritures. Le verrou est automatiquement relâché par PHP à la fin
// du script, y compris en cas d'arrêt anticipé via fail().
$lockPath = SITE_ROOT . '/.save-content.lock';
$lockHandle = @fopen($lockPath, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
    fail(500, "Le serveur est occupé par une autre publication — réessaie dans quelques secondes.");
}

$backupName = null;

// Sauvegarde de l'ancienne version, si elle existe, avant d'écraser.
if (file_exists(CONTENT_FILE)) {
    if (!is_dir(BACKUP_DIR)) {
        if (!mkdir(BACKUP_DIR, 0755, true)) {
            fail(500, "Le dossier content/ n'existe pas et n'a pas pu être créé.");
        }
    }
    if (!is_writable(BACKUP_DIR)) {
        fail(500, "Le dossier content/ n'est pas accessible en écriture sur le serveur (droits/permissions OVH).");
    }

    date_default_timezone_set('Europe/Paris');
    $backupName = 'content-' . date('Y-m-d_H\hi\ms\s') . '.json';
    $backupPath = BACKUP_DIR . '/' . $backupName;

    // Évite un écrasement improbable si deux sauvegardes tombent à la
    // même seconde.
    $suffix = 1;
    while (file_exists($backupPath)) {
        $backupName = 'content-' . date('Y-m-d_H\hi\ms\s') . '-' . $suffix . '.json';
        $backupPath = BACKUP_DIR . '/' . $backupName;
        $suffix++;
    }

    if (!copy(CONTENT_FILE, $backupPath)) {
        fail(500, "Échec de la sauvegarde de l'ancien content.json — rien n'a été modifié par précaution.");
    }
}

$tmpFile = CONTENT_FILE . '.tmp';
if (file_put_contents($tmpFile, $pretty) === false) {
    fail(500, "Échec de l'écriture du nouveau content.json — rien n'a été modifié sur le serveur.");
}
if (!rename($tmpFile, CONTENT_FILE)) {
    @unlink($tmpFile);
    fail(500, "Échec du remplacement de content.json — rien n'a été modifié sur le serveur.");
}

// Vérification finale : on relit ce qui vient réellement d'être écrit
// sur le disque (pas la variable en mémoire) pour détecter tout
// problème d'écriture (disque plein, interruption, permissions
// changeantes en cours de route…). En cas d'anomalie, on restaure
// aussitôt la sauvegarde plutôt que de laisser le site avec un
// content.json cassé en ligne.
clearstatcache(true, CONTENT_FILE);
$writtenRaw = @file_get_contents(CONTENT_FILE);
$writtenData = $writtenRaw !== false ? json_decode($writtenRaw, true) : null;
if ($writtenRaw === false || $writtenData === null || momox_validate_content_shape($writtenData) !== null) {
    if ($backupName !== null && @copy(BACKUP_DIR . '/' . $backupName, CONTENT_FILE)) {
        fail(500, "Le fichier écrit sur le serveur s'est révélé invalide après relecture — la version précédente a été restaurée automatiquement. Réessaie la publication.");
    }
    fail(500, "Le fichier écrit sur le serveur s'est révélé invalide après relecture, et la restauration automatique a échoué — vérifie content.json manuellement sur le serveur au plus vite.");
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

echo json_encode([
    'ok' => true,
    'backup' => $backupName,
], JSON_UNESCAPED_UNICODE);
