<?php
declare(strict_types=1);

/* Fonctions d'affichage partagées par les pages du site (accueil, palmarès, galerie).
   Elles s'appuient sur la variable globale $lang ('fr' ou 'en'). */

function tr(array $item, string $key): string
{
    global $lang;
    $v = $item[$key . '_' . $lang] ?? '';
    return (string) ($v !== '' ? $v : ($item[$key . '_fr'] ?? ''));
}

/* Temps « H:MM:SS » : notation sportive en français (7h38'00), inchangé en anglais. */
function fmt_time(string $t): string
{
    global $lang;
    $t = trim($t);
    if ($lang === 'fr' && preg_match('/^(\d+):(\d{2}):(\d{2})$/', $t, $m)) {
        return $m[1] . 'h' . $m[2] . "'" . $m[3];
    }
    return $t;
}

/* Champ texte avec variante anglaise facultative (clé_en), repli sur la version française. */
function loc(array $item, string $key): string
{
    global $lang;
    $v = $lang === 'en' ? trim((string) ($item[$key . '_en'] ?? '')) : '';
    return $v !== '' ? $v : (string) ($item[$key] ?? '');
}

function rank_label(string $rank): string
{
    global $lang;
    if ($rank !== '' && ctype_digit($rank)) {
        $n = (int) $rank;
        if ($lang === 'fr') {
            return $n . '<sup>' . ($n === 1 ? 'er' : 'e') . '</sup>';
        }
        $v   = $n % 100;
        $suf = ($v >= 11 && $v <= 13) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$n % 10] ?? 'th');
        return $n . '<sup>' . $suf . '</sup>';
    }
    return e($rank);
}

/* Palmarès : les courses cochées « affichée » dans le back office, de la plus récente à la plus ancienne. */
function results_rows(array $c): array
{
    require_once __DIR__ . '/lib_pto.php';
    $rows = [];
    foreach (results_sort(array_values($c['results'] ?? [])) as $r) {
        if ((string) ($r['visible'] ?? '1') === '0') {
            continue;
        }
        $r['year']   = (string) results_year($r) ?: '';
        $r['worlds'] = isset($r['worlds'])
            ? (string) $r['worlds'] === '1'
            : (bool) preg_match('/championnat du monde|world championship/i', ($r['race'] ?? '') . ' ' . ($r['race_en'] ?? ''));
        $rows[] = $r;
    }
    return $rows;
}

/* Une ligne du palmarès (balise <li>). */
function result_li(array $r, string $badgeTitle, string $badgeText): string
{
    $rank  = trim((string) ($r['rank'] ?? ''));
    $isPos = ctype_digit($rank);
    // Pas de classement (DNF…) : tiret à la place du rang, mention à la place du temps.
    $note  = implode(' · ', array_filter([$isPos ? fmt_time((string) ($r['time'] ?? '')) : $rank, tr($r, 'note')], 'strlen'));
    $h  = '<li>';
    $h .= '<span class="mono year">' . e($r['year'] ?? '') . '</span>';
    $h .= '<span class="rank' . ($isPos ? '' : ' dnf') . '">' . ($isPos ? rank_label($rank) : '-') . '</span>';
    $h .= '<span class="what"><strong>' . e(loc($r, 'race')) . '</strong>';
    if (!empty($r['worlds'])) {
        $h .= '<span class="badge" title="' . e($badgeTitle) . '">' . e($badgeText) . '</span>';
    }
    if (loc($r, 'location') !== '') {
        $h .= '<span class="where">' . e(loc($r, 'location')) . '</span>';
    }
    $h .= '</span><span class="note' . ($isPos ? '' : ' dnf') . '">' . e($note) . '</span></li>';
    return $h;
}
