<?php
require_once __DIR__ . '/auth.php';
momox_require_login_api();

header('Content-Type: application/json; charset=utf-8');

$pattern = '/^content-(\d{4})-(\d{2})-(\d{2})_(\d{2})h(\d{2})m(\d{2})s(-\d+)?\.json$/';

$items = [];
if (is_dir(BACKUP_DIR)) {
    foreach (scandir(BACKUP_DIR) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (!preg_match($pattern, $f, $m)) continue;
        $path = BACKUP_DIR . '/' . $f;
        if (!is_file($path)) continue;
        $items[] = [
            'file'  => $f,
            'date'  => "{$m[3]}/{$m[2]}/{$m[1]} à {$m[4]}h{$m[5]}m{$m[6]}",
            'mtime' => filemtime($path),
            'size'  => filesize($path),
        ];
    }
}

// Plus récent en premier.
usort($items, fn($a, $b) => $b['mtime'] <=> $a['mtime']);

echo json_encode(['ok' => true, 'backups' => $items], JSON_UNESCAPED_UNICODE);
