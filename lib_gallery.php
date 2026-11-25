<?php
declare(strict_types=1);

/* Galerie : photos affichées dans les carrousels Highlights (5 plus récentes de chaque type) et sur la page galerie (toutes).
   Les photos se gèrent dans le back office (onglet Galerie) ; elles vivent dans la rubrique « gallery » du contenu.
   Chaque photo : image (chemin relatif), title_fr/en, desc_fr/en, type ('athlete' | 'coach'), date (AAAA-MM-JJ, facultative),
   focus (object-position du carrousel, cadre 3:2, pour les photos verticales ; défaut '50% 30%'), visible ('0' = masquée).
   Les photos téléversées sont rangées dans assets/gallery/ ; les photos historiques restent dans assets/img/. */

const GALLERY_DIR      = __DIR__ . '/assets/gallery';
const GALLERY_MAX_SIDE = 1800;   /* px, grand côté après redimensionnement */
const GALLERY_QUALITY  = 82;     /* qualité JPEG */

function gallery_types(): array
{
    return ['athlete' => ['Athlète', 'Athlete'], 'coach' => ['Coach', 'Coach']];
}

/* Cadrages proposés pour le carrousel (photos verticales) : valeur => libellé. */
function gallery_focuses(): array
{
    return ['50% 30%' => 'Haut (défaut)', '50% 50%' => 'Centre', '50% 70%' => 'Bas'];
}

/* Chemin d'image accepté : uniquement nos dossiers, jamais de « .. ». */
function gallery_valid_image(string $path): bool
{
    return (bool) preg_match('~^assets/(img|gallery)/[A-Za-z0-9][A-Za-z0-9._-]*\.(jpe?g|png|webp)$~', $path);
}

/**
 * Photos visibles, de la plus récente à la plus ancienne (sans date : après les datées, dans l'ordre saisi).
 * $type : 'athlete' | 'coach' | null (tout).
 */
function gallery_items(array $c, ?string $type = null): array
{
    $rows = [];
    foreach (array_values(is_array($c['gallery'] ?? null) ? $c['gallery'] : []) as $i => $g) {
        if (!is_array($g) || (string) ($g['visible'] ?? '1') === '0' || !gallery_valid_image((string) ($g['image'] ?? ''))) {
            continue;
        }
        $g['type'] = isset(gallery_types()[$g['type'] ?? '']) ? (string) $g['type'] : 'athlete';
        if ($type !== null && $g['type'] !== $type) {
            continue;
        }
        $g['_i'] = $i;
        $rows[]  = $g;
    }
    usort($rows, static function (array $a, array $b): int {
        $da = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($a['date'] ?? '')) ? $a['date'] : '';
        $db = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($b['date'] ?? '')) ? $b['date'] : '';
        if ($da !== $db) {
            if ($da === '' || $db === '') {
                return $da === '' ? 1 : -1;
            }
            return strcmp($db, $da);
        }
        return $a['_i'] <=> $b['_i'];
    });
    return $rows;
}

/* Date lisible : « 12 juillet 2026 » / « July 12, 2026 ». Vide si la date n'est pas valide. */
function gallery_date_label(string $iso, string $lang): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return '';
    }
    $months = $lang === 'en'
        ? ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
        : ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $mo = $months[(int) $m[2] - 1];
    $d  = (int) $m[3];
    return $lang === 'en' ? $mo . ' ' . $d . ', ' . $m[1] : ($d === 1 ? '1er' : (string) $d) . ' ' . $mo . ' ' . $m[1];
}

/* Légende d'une photo (HTML) : titre, date, description. Même rendu partout (galerie, carrousel, pop-in). */
function gallery_caption_html(array $g, string $lang): string
{
    $title = trim((string) ($g['title_' . $lang] ?? '')) ?: (string) ($g['title_fr'] ?? '');
    $desc  = trim((string) ($g['desc_' . $lang] ?? '')) ?: (string) ($g['desc_fr'] ?? '');
    $date  = gallery_date_label((string) ($g['date'] ?? ''), $lang);
    $h = '';
    if ($title !== '') {
        $h .= '<strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>';
    }
    if ($date !== '') {
        $h .= '<time class="g-date" datetime="' . htmlspecialchars((string) $g['date'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</time>';
    }
    if ($desc !== '') {
        $h .= '<span>' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return $h;
}

/* Texte alternatif d'une photo : titre + description. */
function gallery_alt(array $g, string $lang): string
{
    $title = trim((string) ($g['title_' . $lang] ?? '')) ?: (string) ($g['title_fr'] ?? '');
    $desc  = trim((string) ($g['desc_' . $lang] ?? '')) ?: (string) ($g['desc_fr'] ?? '');
    return trim($title . ' ' . $desc);
}

/**
 * Enregistre une photo téléversée : vérifie que c'est bien une image (jpeg, png, webp), la remet à l'endroit
 * (EXIF), la réduit à GALLERY_MAX_SIDE px et la ré-encode en JPEG (ce qui supprime les métadonnées GPS/EXIF).
 * Renvoie [chemin relatif, null] ou [null, message d'erreur].
 */
function gallery_store_upload(array $file): array
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        $msg = ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
            ? 'photo trop lourde pour le serveur (' . ini_get('upload_max_filesize') . ' maximum)'
            : 'envoi interrompu, réessayez';
        return [null, $msg];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp) || !function_exists('imagecreatetruecolor')) {
        return [null, 'traitement d\'image indisponible sur ce serveur'];
    }
    $info = @getimagesize($tmp);
    $type = is_array($info) ? (int) $info[2] : 0;
    $w    = is_array($info) ? (int) $info[0] : 0;
    $h    = is_array($info) ? (int) $info[1] : 0;
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $w < 1 || $h < 1) {
        return [null, 'format non pris en charge (JPEG, PNG ou WebP)'];
    }
    if ($w * $h > 60000000) {
        return [null, 'image trop grande (' . round($w * $h / 1000000) . ' Mpx, 60 maximum)'];
    }
    @ini_set('memory_limit', '512M');
    $src = $type === IMAGETYPE_JPEG ? @imagecreatefromjpeg($tmp) : ($type === IMAGETYPE_PNG ? @imagecreatefrompng($tmp) : @imagecreatefromwebp($tmp));
    if (!$src) {
        return [null, 'image illisible'];
    }
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $o    = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $rot  = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot !== 0 && ($r = imagerotate($src, (float) $rot, 0))) {
            $src = $r;
        }
        $w = imagesx($src);
        $h = imagesy($src);
    }
    $scale = min(1.0, GALLERY_MAX_SIDE / max($w, $h));
    $nw    = max(1, (int) round($w * $scale));
    $nh    = max(1, (int) round($h * $scale));
    $dst   = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, (int) imagecolorallocate($dst, 255, 255, 255));   /* PNG/WebP transparents : fond blanc */
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imageinterlace($dst, true);

    if (!is_dir(GALLERY_DIR) && !@mkdir(GALLERY_DIR, 0755, true)) {
        return [null, 'impossible de créer le dossier assets/gallery/ (droits d\'écriture ?)'];
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.jpg';
    if (!@imagejpeg($dst, GALLERY_DIR . '/' . $name, GALLERY_QUALITY)) {
        return [null, 'impossible d\'écrire dans assets/gallery/ (droits d\'écriture ?)'];
    }
    return ['assets/gallery/' . $name, null];
}

/* Supprime les photos téléversées qui ne sont plus référencées (les photos historiques de assets/img/ ne sont jamais touchées). */
function gallery_cleanup(array $rows): void
{
    $used = [];
    foreach ($rows as $r) {
        $used[(string) ($r['image'] ?? '')] = true;
    }
    foreach (glob(GALLERY_DIR . '/*.jpg') ?: [] as $f) {
        if (!isset($used['assets/gallery/' . basename($f)])) {
            @unlink($f);
        }
    }
}
