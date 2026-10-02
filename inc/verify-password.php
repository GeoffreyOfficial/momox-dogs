<?php
/**
 * Vérifie le mot de passe re-saisi juste avant publication, sans
 * toucher au contenu du site. Appelé par la modale de confirmation
 * dans admin.php avant l'envoi réel à save-content.php, pour donner
 * un retour immédiat (mauvais mot de passe / verrouillage) sans avoir
 * à ré-envoyer tout content.json.
 *
 * Rappel important : ce endpoint est un confort côté interface. Le
 * vrai rempart est la revérification indépendante faite dans
 * save-content.php — un appel direct à ce fichier sans passer par ici
 * ne permet donc pas de publier sans le bon mot de passe.
 */
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function verifyFail(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => false], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    verifyFail(405, ['message' => "Méthode non autorisée."]);
}

$raw = file_get_contents('php://input');
$data = $raw !== false ? json_decode($raw, true) : null;
$password = (is_array($data) && isset($data['password']) && is_string($data['password']))
    ? $data['password']
    : '';

$result = momox_verify_publish_password($password);

if ($result['locked']) {
    verifyFail(423, [
        'locked' => true,
        'retry_after' => $result['retry_after'],
        'message' => "Trop de tentatives — réessaie dans quelques secondes.",
    ]);
}

if (!$result['ok']) {
    verifyFail(403, [
        'locked' => false,
        'message' => "Mot de passe incorrect. Réessaie.",
    ]);
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
