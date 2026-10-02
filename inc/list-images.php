<?php
/**
 * Liste toutes les photos présentes sur le serveur, dans images/ et
 * images/galerie/, pour la modale « Choisir une image déjà sur le
 * serveur » de admin.php. Lecture seule — aucune écriture ici.
 *
 * Le dossier images/icons/ (logos, favicons) est volontairement
 * ignoré, comme pour list-unused-images.php : ces fichiers ne sont
 * pas pilotés depuis content.json et n'ont rien à faire dans un
 * sélecteur de photos de contenu.
 */
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_li(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    fail_li(405, "Méthode non autorisée.");
}

/**
 * Scanne un dossier (non récursif) et renvoie les images qui s'y
 * trouvent directement — les sous-dossiers (galerie/, icons/) sont
 * ignorés ici et traités séparément par leur propre appel.
 */
function scanImagesFlat(string $absDir, string $relPrefix, string $bucket): array {
    $items = [];
    if (!is_dir($absDir)) return $items;
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir . '/' . $f;
        if (!is_file($abs)) continue; // ignore les sous-dossiers
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) continue;
        $items[] = [
            'path'   => $relPrefix . $f,
            'name'   => $f,
            'bucket' => $bucket,
            'size'   => filesize($abs),
            'mtime'  => filemtime($abs),
        ];
    }
    return $items;
}

$images = array_merge(
    scanImagesFlat(IMAGES_DIR, 'images/', 'images'),
    scanImagesFlat(IMAGES_DIR . '/galerie', 'images/galerie/', 'galerie')
);

// Les plus récentes en premier — les photos qu'on vient d'envoyer sont
// les plus probables à vouloir réutiliser tout de suite.
usort($images, fn($a, $b) => $b['mtime'] <=> $a['mtime']);

echo json_encode(['ok' => true, 'images' => $images], JSON_UNESCAPED_UNICODE);
