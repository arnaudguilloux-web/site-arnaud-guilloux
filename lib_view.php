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

/* Famille de course affichée en label : IRONMAN 70.3, IRONMAN ou Challenge (vide pour les autres courses).
   Déduite du nom ; les championnats du monde IRONMAN reçoivent IRONMAN en plus du badge WC. */
function race_label(array $r): string
{
    $name = trim((string) ($r['race'] ?? ''));
    if (!empty($r['worlds']) && preg_match('/IRONMAN/i', $name . ' ' . ($r['race_en'] ?? ''))) {
        return 'IRONMAN'; // championnats du monde IRONMAN : label IRONMAN en plus du badge WC
    }
    if (preg_match('/^IRONMAN\b.*\b70\.3\b/i', $name)) {
        return 'IRONMAN 70.3';
    }
    if (preg_match('/^IRONMAN\b/i', $name)) {
        return 'IRONMAN';
    }
    if (preg_match('/^Challenge\b/i', $name)) {
        return 'Challenge';
    }
    return '';
}

/* Une ligne du palmarès (balise <li>). Si la course vient de PTO, la ligne renvoie vers sa page PTO (nouvel onglet). */
function result_li(array $r, string $badgeTitle, string $badgeText): string
{
    global $lang;
    $rank   = trim((string) ($r['rank'] ?? ''));
    $isPos  = ctype_digit($rank);
    // Pas de classement (DNF…) : tiret à la place du rang, mention à la place du temps.
    $note   = implode(' · ', array_filter([$isPos ? fmt_time((string) ($r['time'] ?? '')) : $rank, tr($r, 'note')], 'strlen'));
    $pto    = trim((string) ($r['pto'] ?? ''));
    $linked = (bool) preg_match('#^[a-z0-9][a-z0-9_-]*/\d{4}$#i', $pto);
    $race   = e(loc($r, 'race'));
    if ($linked) {
        $label = loc($r, 'race') . ($lang === 'en' ? ' — result on PTO (opens in a new tab)' : ' — résultat sur PTO (nouvel onglet)');
        $race  = '<a class="pto-link" href="https://stats.protriathletes.org/race/' . e($pto) . '/results" target="_blank" rel="noopener noreferrer" aria-label="' . e($label) . '">'
               . $race . ext_icon_svg() . '</a>';
    }
    $h  = '<li' . ($linked ? ' class="has-link"' : '') . '>';
    $h .= '<span class="mono year">' . e($r['year'] ?? '') . '</span>';
    $h .= '<span class="rank' . ($isPos ? '' : ' dnf') . '">' . ($isPos ? rank_label($rank) : '-') . '</span>';
    $h .= '<span class="what"><strong>' . $race . '</strong>';
    if (!empty($r['worlds'])) {
        $h .= '<span class="badge" title="' . e($badgeTitle) . '">' . e($badgeText) . '</span>';
    }
    $kind = race_label($r);
    if ($kind !== '') {
        $h .= '<span class="kind">' . e($kind) . '</span>';
    }
    if (loc($r, 'location') !== '') {
        $h .= '<span class="where">' . e(loc($r, 'location')) . '</span>';
    }
    $h .= '</span><span class="note' . ($isPos ? '' : ' dnf') . '">' . e($note) . '</span></li>';
    return $h;
}

/* Coaching : lignes d'offres (seules celles qui ont un titre). */
function coaching_items(array $c): array
{
    return array_values(array_filter($c['coaching_items'] ?? [], static function ($i) {
        return is_array($i) && trim((string) ($i['title_fr'] ?? '')) !== '';
    }));
}

/* Témoignages : seulement ceux qui ont une citation. */
function testimonials(array $c): array
{
    return array_values(array_filter($c['testimonials'] ?? [], static function ($q) {
        return is_array($q) && trim((string) ($q['quote'] ?? '')) !== '';
    }));
}

/** Picto « s'ouvre dans un nouvel onglet » (partagé : palmarès, partenaires). */
function ext_icon_svg(): string
{
    return '<svg class="ext" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg>';
}

/** Y a-t-il au moins une course à venir ? (le lien « Calendrier » n'existe alors que dans ce cas) */
function has_upcoming(array $c): bool
{
    $today = date('Y-m-d');
    foreach ($c['calendar'] ?? [] as $ev) {
        if (is_array($ev) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($ev['date'] ?? '')) && $ev['date'] >= $today) {
            return true;
        }
    }
    return false;
}

/**
 * En-tête commun à toutes les pages : marque, menu, bascule FR/EN.
 * Menu : « L'athlète » (sous-menu d'ancres vers les sections), « Le coach » (page Coaching), « Mes annonces » (lien externe).
 * $current : 'highlights' | 'palmares' | 'coaching' | '' (page courante, mise en avant dans le menu).
 * $onHome  : sur l'accueil, les liens du sous-menu sont des ancres (#id, suivies par le scroll-spy).
 */
function site_header(string $lang, array $home, array $self, array $c, string $current = '', bool $onHome = false): string
{
    $en = $lang === 'en';
    $T  = $en
        ? ['athlete' => 'The Athlete', 'coach' => 'The Coach', 'ads' => 'My listings', 'new_tab' => 'opens in a new tab',
           'sub' => [
               'highlights' => ['Highlights', 'Photos from the big races'],
               'palmares'   => ['Results', 'The full race record'],
               'partenaires' => ['Partners', 'Those who back me'],
               'contact'    => ['Contact', 'A question, a project'],
           ]]
        : ['athlete' => "L'Athlète", 'coach' => 'Le Coach', 'ads' => 'Mes annonces', 'new_tab' => 'nouvel onglet',
           'sub' => [
               'highlights' => ['Highlights', 'Photos des grandes courses'],
               'palmares'   => ['Palmarès', 'Tous les résultats'],
               'partenaires' => ['Partenaires', 'Ceux qui m\'accompagnent'],
               'contact'    => ['Contact', 'Une question, un projet'],
           ]];

    /* Lien externe « Mes annonces » : réglable dans l'admin (Réglages), sinon page d'accueil Troc-Vélo. */
    $set    = $c['settings'] ?? [];
    $adsUrl = trim((string) ($set['ads_url_' . $lang] ?? ''));
    if (!preg_match('#^https?://#i', $adsUrl)) {
        $adsUrl = $en ? 'https://www.troc-velo.com/en' : 'https://www.troc-velo.com/fr-fr';
    }

    $suffix = $en ? '?lang=en' : '';
    $base   = $home['fr'];
    /* Pages qui prolongent une section : le lien du sous-menu y pointe quand on est dessus. */
    $pages  = ['highlights' => 'galerie.php', 'palmares' => 'palmares.php'];

    $inAthlete = isset($T['sub'][$current]);
    $coachOn   = $current === 'coaching';

    $h  = '<header class="top"><div class="wrap">';
    $h .= '<a class="brand" href="' . e($home[$lang]) . '">Arnaud <strong>Guilloux</strong></a>';
    $h .= '<nav class="nav" aria-label="Sections">';

    /* L'athlète + sous-menu */
    $h .= '<div class="has-sub">';
    $h .= '<button class="nav-trigger' . ($inAthlete ? ' on' : '') . '" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="sub-athlete">'
        . e($T['athlete'])
        . '<svg class="chev" viewBox="0 0 12 8" width="10" height="7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 1.5l5 5 5-5"/></svg></button>';
    $h .= '<div class="sub" id="sub-athlete"><ul>';
    foreach ($T['sub'] as $id => [$label, $hint]) {
        if ($id === $current && isset($pages[$id])) {
            $href = $base . $pages[$id] . $suffix;
        } else {
            $href = ($onHome ? '' : $home[$lang]) . '#' . $id;
        }
        $on = $id === $current ? ' class="on" aria-current="page"' : '';
        $h .= '<li><a href="' . e($href) . '"' . $on . '><strong>' . e($label) . '</strong><span>' . e($hint) . '</span></a></li>';
    }
    $h .= '</ul></div></div>';

    /* Le coach */
    $h .= '<a href="' . e($base . 'coaching.php' . $suffix) . '"' . ($coachOn ? ' class="on" aria-current="page"' : '') . '>' . e($T['coach']) . '</a>';

    /* Mes annonces (externe) */
    $h .= '<a href="' . e($adsUrl) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($T['ads'] . ' — ' . $T['new_tab']) . '">' . e($T['ads']) . ext_icon_svg() . '</a>';
    $h .= '</nav>';

    $h .= '<div class="lang" aria-label="Langue / Language">';
    foreach (['fr' => 'FR', 'en' => 'EN'] as $code => $txt) {
        $dest = $onHome ? $home[$code] : $self[$code];
        $h .= '<a href="' . e($dest) . '" hreflang="' . $code . '" lang="' . $code . '"' . ($lang === $code ? ' aria-current="true"' : '') . '>' . $txt . '</a>';
    }
    $h .= '</div></div></header>';
    $h .= '<script src="' . e($base) . 'assets/nav.js?v=1" defer></script>';
    return $h;
}
