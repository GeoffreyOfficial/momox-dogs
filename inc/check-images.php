<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_ci(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail_ci(405, "Méthode non autorisée.");
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || !isset($body['paths']) || !is_array($body['paths'])) {
    fail_ci(400, "Requête invalide : liste de chemins attendue.");
}

/**
 * Cherche, dans le dossier attendu (images/ ou images/galerie/), des
 * fichiers déjà présents dont le nom ressemble fortement à celui
 * demandé mais absent — indice probable d'une faute de frappe ou
 * d'une extension différente plutôt que d'une vraie photo manquante.
 */
function findCloseMatches(string $expectedRelPath): array {
    $dir = dirname($expectedRelPath); // "images" ou "images/galerie"
    $absDir = SITE_ROOT . '/' . $dir;
    if (!is_dir($absDir)) return [];

    $expectedBase = basename($expectedRelPath);
    $expectedName = strtolower(pathinfo($expectedBase, PATHINFO_FILENAME));
    $expectedExt  = strtolower(pathinfo($expectedBase, PATHINFO_EXTENSION));

    $candidates = [];
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (!is_file($absDir . '/' . $f)) continue;
        if ($f === $expectedBase) continue; // n'arrive pas ici de toute façon (sinon "exists")

        $name = strtolower(pathinfo($f, PATHINFO_FILENAME));
        $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));

        if ($name === $expectedName && $ext === $expectedExt) {
            // Ne devrait pas arriver (aurait été détecté comme existant),
            // mais on l'ignore par sécurité.
            continue;
        }
        if ($name === $expectedName && $ext !== $expectedExt) {
            $candidates[] = ['file' => $dir . '/' . $f, 'reason' => 'extension', 'distance' => 0];
            continue;
        }
        if (strcasecmp($name, $expectedName) === 0 && $ext === $expectedExt) {
            $candidates[] = ['file' => $dir . '/' . $f, 'reason' => 'case', 'distance' => 0];
            continue;
        }
        $dist = levenshtein($name, $expectedName);
        if ($dist > 0 && $dist <= 2 && abs(strlen($name) - strlen($expectedName)) <= 2) {
            $candidates[] = ['file' => $dir . '/' . $f, 'reason' => 'similar', 'distance' => $dist];
        }
    }

    // Priorité : extension > casse > proximité, puis distance croissante.
    $priority = ['extension' => 0, 'case' => 1, 'similar' => 2];
    usort($candidates, function ($a, $b) use ($priority) {
        $pa = $priority[$a['reason']] ?? 9;
        $pb = $priority[$b['reason']] ?? 9;
        if ($pa !== $pb) return $pa <=> $pb;
        return $a['distance'] <=> $b['distance'];
    });

    return array_slice($candidates, 0, 3);
}

$results = [];
foreach ($body['paths'] as $requested) {
    $requested = is_string($requested) ? $requested : '';
    $valid = momox_validate_relative_image_path($requested);

    if ($valid === null) {
        $results[] = [
            'path' => $requested,
            'valid' => false,
            'exists' => false,
            'message' => "Chemin d'image invalide (dossier ou extension non autorisée).",
            'suggestions' => [],
        ];
        continue;
    }

    $abs = SITE_ROOT . '/' . $valid;
    $exists = is_file($abs);

    $results[] = [
        'path' => $valid,
        'valid' => true,
        'exists' => $exists,
        'suggestions' => $exists ? [] : findCloseMatches($valid),
    ];
}

echo json_encode(['ok' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
