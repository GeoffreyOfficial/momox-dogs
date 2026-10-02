<?php
/**
 * Configuration de l'admin Momox Dogs.
 *
 * ⚠️ POUR CHANGER LE MOT DE PASSE :
 * remplace simplement le texte entre guillemets ci-dessous, puis
 * ré-envoie ce fichier sur OVH (dossier inc/). C'est tout.
 */
define('ADMIN_PASSWORD', 'AvAtAr1613*');

/* ---- Chemins (ne pas modifier) ---------------------------------- */
// Racine du site = dossier qui contient index.html, content.json, images/
define('SITE_ROOT', dirname(__DIR__));
define('CONTENT_FILE', SITE_ROOT . '/content.json');
// Les anciennes versions de content.json sont gardées dans ce dossier,
// nommées content-AAAA-MM-JJ_HHhMMmSSs.json
define('BACKUP_DIR', SITE_ROOT . '/content');
define('IMAGES_DIR', SITE_ROOT . '/images');

// Taille maximale acceptée pour une photo envoyée (en octets). 10 Mo ici.
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);

// Extensions de photo autorisées
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

/**
 * Vérifie que $data ressemble bien à un content.json complet avant
 * toute publication : un objet JSON (pas une liste, pas un scalaire)
 * qui possède au minimum tous les champs structurants attendus, avec
 * le bon type de base. C'est le dernier rempart avant écriture sur le
 * serveur : sans ce contrôle, un JSON techniquement valide mais vide,
 * partiel ou de mauvaise forme (ex. juste "42" ou une liste) pourrait
 * écraser silencieusement tout le site. Retourne null si valide, sinon
 * un message d'erreur explicite.
 */
function momox_validate_content_shape($data): ?string {
    if (!is_array($data)) {
        return "Le contenu envoyé n'est pas un objet JSON (reçu : " . gettype($data) . ").";
    }

    $requiredArrayKeys = ['tags', 'services', 'galerie', 'nav'];
    $requiredObjectKeys = ['visibility', 'contact', 'hero', 'presentation', 'pourquoi', 'footer', 'seo', 'images', 'theme'];

    foreach (array_merge($requiredArrayKeys, $requiredObjectKeys) as $key) {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return "Le champ « {$key} » est manquant ou invalide dans le contenu envoyé — structure inattendue (liste au lieu d'un objet, ou données incomplètes).";
        }
    }
    foreach (['email', 'telephone'] as $key) {
        if (!array_key_exists($key, $data['contact']) || !is_string($data['contact'][$key]) || trim($data['contact'][$key]) === '') {
            return "Le champ « contact.{$key} » est manquant ou vide — contenu refusé par sécurité.";
        }
    }
    return null;
}

/**
 * Recense tous les chemins d'image référencés dans un tableau de
 * contenu (structure de content.json) : images principales, cours,
 * galerie, image de partage SEO. Utilisé à la fois pour vérifier
 * qu'une photo est bien utilisée avant publication, et pour repérer
 * les photos inutilisées (nettoyeur de photos). Le dossier
 * images/icons/ (logos, favicons) n'est jamais concerné : ces
 * fichiers ne sont pas pilotés depuis content.json.
 */
function momox_collect_image_paths(array $content): array {
    $paths = [];
    $add = function ($p) use (&$paths) {
        if (is_string($p)) {
            $p = trim($p);
            if ($p !== '') $paths[$p] = true;
        }
    };
    if (isset($content['images']) && is_array($content['images'])) {
        foreach (['hero', 'contact', 'portrait'] as $k) {
            if (isset($content['images'][$k])) $add($content['images'][$k]);
        }
    }
    if (isset($content['services']) && is_array($content['services'])) {
        foreach ($content['services'] as $s) {
            if (is_array($s) && isset($s['image'])) $add($s['image']);
        }
    }
    if (isset($content['galerie']) && is_array($content['galerie'])) {
        foreach ($content['galerie'] as $g) {
            if (is_array($g) && isset($g['src'])) $add($g['src']);
        }
    }
    if (isset($content['seo']) && is_array($content['seo']) && isset($content['seo']['shareImage'])) {
        $add($content['seo']['shareImage']);
    }
    return array_keys($paths);
}
/**
 * Vérifie que le fichier temporaire uploadé est réellement une image
 * du type annoncé par son extension — pas seulement un fichier
 * renommé. Protège contre un fichier corrompu, tronqué pendant
 * l'envoi, ou simplement mal nommé (ex. un .pdf renommé en .jpg).
 * Retourne null si tout va bien, sinon un message d'erreur explicite.
 */
function momox_verify_image_content(string $tmpPath, string $ext): ?string {
    $info = @getimagesize($tmpPath);
    if ($info !== false && isset($info[2])) {
        $map = [
            IMAGETYPE_JPEG => ['jpg', 'jpeg'],
            IMAGETYPE_PNG  => ['png'],
            IMAGETYPE_GIF  => ['gif'],
            IMAGETYPE_WEBP => ['webp'],
        ];
        $expectedExts = $map[$info[2]] ?? null;
        if ($expectedExts === null) {
            return "Ce type d'image n'est pas pris en charge par le serveur.";
        }
        if (!in_array($ext, $expectedExts, true)) {
            return "Le contenu réel de ce fichier ne correspond pas à son extension .{$ext} — il semble avoir été renommé par erreur.";
        }
        return null;
    }

    /*
     * getimagesize() a échoué : soit le fichier est vraiment corrompu,
     * soit il est dans un format que getimagesize() ne sait pas lire
     * du tout (typiquement AVIF ou HEIC/HEIF), ce qui arrive souvent
     * avec une photo enregistrée depuis Google Images ou un iPhone,
     * même quand le fichier se retrouve nommé « .jpg ». On utilise
     * finfo (détection par contenu, indépendante de getimagesize)
     * pour donner un message qui dit vraiment ce qui se passe plutôt
     * que le message générique « fichier corrompu », trompeur dans ce
     * cas précis.
     */
    $realMime = null;
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $realMime = @finfo_file($finfo, $tmpPath) ?: null;
            finfo_close($finfo);
        }
    }

    $unsupportedFormats = [
        'image/avif'   => 'AVIF',
        'image/heic'   => 'HEIC',
        'image/heif'   => 'HEIF',
        'image/x-heic' => 'HEIC',
        'image/x-heif' => 'HEIF',
    ];

    if ($realMime !== null && isset($unsupportedFormats[$realMime])) {
        $formatName = $unsupportedFormats[$realMime];
        return "Ce fichier est en réalité au format {$formatName} et non une vraie image .{$ext} — cela arrive souvent avec une photo enregistrée depuis Google Images ou envoyée depuis un iPhone. Le serveur ne sait pas encore convertir ce format : ouvre la photo (aperçu / galerie photos) et exporte-la en JPEG ou PNG, puis envoie ce nouveau fichier.";
    }

    return "Ce fichier ne semble pas être une image valide (fichier corrompu, tronqué, ou d'un autre type que ce que son nom indique).";
}

/**
 * Valide un chemin d'image relatif à la racine du site, du type
 * "images/chien.jpg" ou "images/galerie/chien.jpg". Retourne le
 * chemin nettoyé si valide, ou null sinon. Le format est strictement
 * limité par expression régulière (lettres/chiffres/tirets/points),
 * ce qui empêche par construction toute tentative de sortir du
 * dossier images/ (pas de "..", pas de "/" supplémentaire) — et
 * exclut par construction le dossier images/icons/, jamais une cible
 * valide pour les photos gérées depuis l'admin.
 */
function momox_validate_relative_image_path(?string $relPath): ?string {
    if ($relPath === null) return null;
    $relPath = trim($relPath);
    if ($relPath === '') return null;
    if (!preg_match('#^images/(galerie/)?[A-Za-z0-9._-]+\.[A-Za-z0-9]+$#', $relPath)) {
        return null;
    }
    $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) return null;
    return $relPath;
}
