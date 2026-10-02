<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

function fail_lu(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_file(CONTENT_FILE)) {
    fail_lu(500, "content.json introuvable sur le serveur.");
}
$liveRaw = file_get_contents(CONTENT_FILE);
$liveContent = json_decode($liveRaw, true);
if (!is_array($liveContent)) {
    fail_lu(500, "content.json est illisible (JSON invalide) — nettoyage interrompu par précaution.");
}

$usedPaths = array_flip(momox_collect_image_paths($liveContent));

/* -------- 1) Toutes les photos présentes sur le serveur (hors images/icons/) -------- */
function scanImageDir(string $absDir, string $relPrefix): array {
    $items = [];
    if (!is_dir($absDir)) return $items;
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir . '/' . $f;
        if (!is_file($abs)) continue; // ignore les sous-dossiers (galerie/, icons/)
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) continue;
        $items[] = [
            'path'  => $relPrefix . $f,
            'size'  => filesize($abs),
            'mtime' => filemtime($abs),
        ];
    }
    return $items;
}

$onDisk = array_merge(
    scanImageDir(IMAGES_DIR, 'images/'),
    scanImageDir(IMAGES_DIR . '/galerie', 'images/galerie/')
);

/* -------- 2) Celles qui ne sont référencées nulle part -------- */
$unused = array_values(array_filter($onDisk, function ($item) use ($usedPaths) {
    return !isset($usedPaths[$item['path']]);
}));

/* -------- 3) Pour chacune, quelles anciennes sauvegardes la référencent encore -------- */
$backupPathsByFile = [];
if (is_dir(BACKUP_DIR)) {
    $pattern = '/^content-\d{4}-\d{2}-\d{2}_\d{2}h\d{2}m\d{2}s(-\d+)?\.json$/';
    foreach (scandir(BACKUP_DIR) as $f) {
        if (!preg_match($pattern, $f)) continue;
        $path = BACKUP_DIR . '/' . $f;
        if (!is_file($path)) continue;
        $raw = @file_get_contents($path);
        $data = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($data)) continue; // sauvegarde corrompue : ignorée, ne bloque rien
        $backupPathsByFile[$f] = momox_collect_image_paths($data);
    }
}

foreach ($unused as &$item) {
    $refs = [];
    foreach ($backupPathsByFile as $backupFile => $paths) {
        if (in_array($item['path'], $paths, true)) $refs[] = $backupFile;
    }
    $item['backups'] = $refs;

    // Le fichier est présent sur le disque et son extension est autorisée
    // (voir scanImageDir), mais rien ne garantit que son contenu est bien
    // une image décodable : un fichier corrompu, ou dans un format que le
    // navigateur ne sait pas afficher (ex. AVIF/HEIC renommé en .jpg), se
    // retrouve listé normalement alors que sa miniature ne s'affichera
    // jamais. On le signale explicitement plutôt que de laisser deviner
    // depuis une miniature cassée.
    $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
    $invalidReason = momox_verify_image_content(SITE_ROOT . '/' . $item['path'], $ext);
    if ($invalidReason !== null) {
        $item['invalidReason'] = $invalidReason;
    }
}
unset($item);

// Les plus grosses / plus anciennes en premier n'a pas d'intérêt particulier ;
// on trie par chemin pour un affichage stable et prévisible.
usort($unused, fn($a, $b) => strcmp($a['path'], $b['path']));

echo json_encode(['ok' => true, 'unused' => $unused], JSON_UNESCAPED_UNICODE);
