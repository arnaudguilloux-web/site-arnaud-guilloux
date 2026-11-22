<?php
declare(strict_types=1);

/*
 * Import des résultats depuis la page PTO d'Arnaud (stats.protriathletes.org).
 * PTO n'a pas d'API publique : la page est rendue côté serveur, on lit son tableau de résultats.
 * L'import ne touche qu'à la place, au temps et à la date : lieux, traductions, notes,
 * affichage (œil) et Mondial restent ceux saisis dans le back office.
 */

const PTO_ATHLETE_URL = 'https://stats.protriathletes.org/athlete/arnaud-guilloux';

/** Télécharge la page PTO. Retourne null en cas d'échec. */
function pto_fetch(string $url = PTO_ATHLETE_URL): ?string
{
    $ua = 'Mozilla/5.0 (compatible; site-arnaud-guilloux results import)';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_HTTPHEADER     => ['Accept-Language: en'],
        ]);
        $html = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return (is_string($html) && $code === 200) ? $html : null;
    }
    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'header' => "User-Agent: $ua\r\nAccept-Language: en\r\n"]]);
    $html = @file_get_contents($url, false, $ctx);
    return is_string($html) && $html !== '' ? $html : null;
}

/**
 * Lit le tableau des résultats. Retourne une ligne par course :
 * ['pto' => 'slug/année', 'year', 'date' (Y-m-d), 'name', 'rank', 'time' (H:MM:SS ou '')].
 */
function pto_parse(string $html): array
{
    $dom = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $xp  = new DOMXPath($dom);
    $has = static function (string $cls): string {
        return 'td[contains(concat(" ", normalize-space(@class), " "), " ' . $cls . ' ")]';
    };
    $rows = [];
    $trs  = $xp->query('//table[contains(concat(" ", normalize-space(@class), " "), " athlete-results ")]//tr[' . $has('race') . ']');
    foreach ($trs ?: [] as $tr) {
        $a = $xp->query('.//' . $has('race') . '//a', $tr)->item(0);
        if (!$a instanceof DOMElement || !preg_match('#/race/([^/]+)/(\d{4})/results#', (string) $a->getAttribute('href'), $m)) {
            continue;
        }
        $cell = static function (string $cls) use ($xp, $tr, $has): string {
            $n = $xp->query('.//' . $has($cls), $tr)->item(0);
            return $n ? trim((string) $n->textContent) : '';
        };
        // Temps : premier nœud texte de la cellule (la cellule contient aussi le détail des points).
        $time = '';
        $ov   = $xp->query('.//' . $has('overall'), $tr)->item(0);
        if ($ov) {
            foreach ($ov->childNodes as $n) {
                if ($n->nodeType === XML_TEXT_NODE && trim((string) $n->textContent) !== '') {
                    $time = trim((string) $n->textContent);
                    break;
                }
            }
        }
        if (!preg_match('/^\d+:\d{2}(:\d{2})?$/', $time)) {
            $time = '';
        }
        $place = strtoupper($cell('place'));
        $rank  = ctype_digit($place) ? $place : (preg_match('/^(DNF|DNS|DSQ)$/', $place) ? $place : '');
        $d     = DateTime::createFromFormat('!d M Y', $cell('date') . ' ' . $m[2]);
        $rows[] = [
            'pto'  => $m[1] . '/' . $m[2],
            'year' => $m[2],
            'date' => $d ? $d->format('Y-m-d') : '',
            'name' => trim((string) $a->textContent),
            'rank' => $rank,
            'time' => $time,
        ];
    }
    return $rows;
}

/* Lieux connus par course PTO (PTO ne les donne pas) : [FR, EN]. Vide = à renseigner dans le back office. */
function pto_locations(): array
{
    return [
        'im-hawaii'                          => ['Kailua-Kona, Hawaï', 'Kailua-Kona, Hawaii'],
        'ironman-nice-world-championships'   => ['Nice, France', ''],
        'im-st-george-world-championships'   => ['St. George, Utah', ''],
        'challenge-roth'                     => ['Roth, Allemagne', 'Roth, Germany'],
        'im-brasil'                          => ['Florianópolis', ''],
        'im-texas'                           => ['The Woodlands, États-Unis', 'The Woodlands, USA'],
        'im-703-california'                  => ['Oceanside, Californie', 'Oceanside, California'],
        'im-arizona'                         => ['Tempe, Arizona', ''],
        'im-chattanooga'                     => ['Tennessee, États-Unis', 'Tennessee, USA'],
        'im-lanzarote'                       => ['Lanzarote, Espagne', 'Lanzarote, Spain'],
        'ironman-703-valencia'               => ['Valence, Espagne', 'Valencia, Spain'],
        'im-new-zealand'                     => ['Taupō', ''],
        'im-703-zell-am-see'                 => ['Zell am See, Autriche', 'Zell am See, Austria'],
        'im-lake-placid'                     => ['New York, États-Unis', 'New York, USA'],
        'im-703-les-sables-dolonne'          => ['Vendée', ''],
        'im-cairns'                          => ['Australie', 'Australia'],
        'im-703-pays-daix'                   => ['Aix-en-Provence', ''],
        'im-703-campeche'                    => ['Campeche, Mexique', 'Campeche, Mexico'],
        'im-cozumel'                         => ['Cozumel, Mexique', 'Cozumel, Mexico'],
        'challenge-sanremo'                  => ['Sanremo, Italie', 'Sanremo, Italy'],
        'alpe-dhuez-l'                       => ["Alpe d'Huez", ''],
        'im-austria'                         => ['Klagenfurt', ''],
        'im-south-africa'                    => ['Gqeberha, Afrique du Sud', 'Gqeberha, South Africa'],
        'im-703-vichy'                       => ['Vichy', ''],
        'im-703-warsaw'                      => ['Varsovie', 'Warsaw'],
        'im-florida'                         => ['Panama City Beach, Floride', 'Panama City Beach, Florida'],
        'challenge-gran-canaria'             => ['Grande Canarie, Espagne', 'Gran Canaria, Spain'],
        'im-wales'                           => ['Tenby, pays de Galles', 'Tenby, Wales'],
        'im-hamburg'                         => ['Hambourg', 'Hamburg'],
        'im-703-barcelona'                   => ['Calella, Espagne', 'Calella, Spain'],
        'im-703-dubai'                       => ['Dubaï, Émirats arabes unis', 'Dubai, UAE'],
        'im-uk'                              => ['Bolton, Royaume-Uni', 'Bolton, UK'],
        'im-703-elsinore'                    => ['Lake Elsinore, Californie', 'Lake Elsinore, California'],
        'im-703-bahrain'                     => ['Bahreïn', 'Bahrain'],
        'challenge-sardinia'                 => ['Sardaigne, Italie', 'Sardinia, Italy'],
        'im-france'                          => ['Nice, France', ''],
    ];
}

/* Continents (filtre du palmarès) : code => [FR, EN]. */
function continent_names(): array
{
    return [
        'eu' => ['Europe', 'Europe'],
        'na' => ['Amérique du Nord', 'North America'],
        'sa' => ['Amérique du Sud', 'South America'],
        'af' => ['Afrique', 'Africa'],
        'as' => ['Asie', 'Asia'],
        'oc' => ['Océanie', 'Oceania'],
    ];
}

/* Continent de chaque course PTO connue (clé PTO sans l'année). Hawaï est rangé en Amérique du Nord (État américain). */
function pto_continents(): array
{
    return [
        'im-hawaii' => 'na', 'ironman-nice-world-championships' => 'eu', 'im-st-george-world-championships' => 'na',
        'challenge-roth' => 'eu', 'im-brasil' => 'sa', 'im-texas' => 'na', 'im-703-california' => 'na', 'im-arizona' => 'na',
        'im-chattanooga' => 'na', 'im-lanzarote' => 'eu', 'ironman-703-valencia' => 'eu', 'im-new-zealand' => 'oc',
        'im-703-zell-am-see' => 'eu', 'im-lake-placid' => 'na', 'im-703-les-sables-dolonne' => 'eu', 'im-cairns' => 'oc',
        'im-703-pays-daix' => 'eu', 'im-703-campeche' => 'na', 'im-cozumel' => 'na', 'challenge-sanremo' => 'eu',
        'alpe-dhuez-l' => 'eu', 'im-austria' => 'eu', 'im-south-africa' => 'af', 'im-703-vichy' => 'eu', 'im-703-warsaw' => 'eu',
        'im-florida' => 'na', 'challenge-gran-canaria' => 'eu', 'im-wales' => 'eu', 'im-hamburg' => 'eu', 'im-703-barcelona' => 'eu',
        'im-703-dubai' => 'as', 'im-uk' => 'eu', 'im-703-elsinore' => 'na', 'im-703-bahrain' => 'as', 'challenge-sardinia' => 'eu',
        'im-france' => 'eu', 'ironman-703-loire-valley' => 'eu', '1406inn-international-triathlon' => 'eu',
    ];
}

/* Continent d'une course : réglage manuel du back office, sinon clé PTO connue, sinon pays/région cités dans le lieu, sinon '' (non classée). */
function race_continent(array $r): string
{
    $names = continent_names();
    $set   = (string) ($r['continent'] ?? '');
    if (isset($names[$set])) {
        return $set;
    }
    $slug = strtolower((string) strtok((string) ($r['pto'] ?? ''), '/'));
    $map  = pto_continents();
    if ($slug !== '' && isset($map[$slug])) {
        return $map[$slug];
    }
    $loc   = mb_strtolower(($r['location'] ?? '') . ' ' . ($r['location_en'] ?? ''));
    $hints = [
        'eu' => ['france', 'espagne', 'spain', 'italie', 'italy', 'allemagne', 'germany', 'autriche', 'austria', 'royaume-uni', 'uk', 'galles', 'wales', 'pologne', 'poland', 'suisse', 'switzerland', 'portugal', 'belgique', 'belgium', 'pays-bas', 'netherlands', 'croatie', 'croatia', 'danemark', 'denmark', 'suède', 'sweden', 'norvège', 'norway', 'finlande', 'finland', 'grèce', 'greece', 'irlande', 'ireland', 'bretagne', 'verdon', 'baudreix', 'alpe d', 'gravelines'],
        'na' => ['états-unis', 'usa', 'united states', 'mexique', 'mexico', 'canada', 'californie', 'california', 'floride', 'florida', 'texas', 'hawaï', 'hawaii', 'arizona', 'utah', 'tennessee'],
        'sa' => ['brésil', 'brazil', 'argentine', 'chili', 'chile', 'colombie', 'colombia', 'pérou', 'peru'],
        'af' => ['afrique', 'africa', 'maroc', 'morocco', 'égypte', 'egypt'],
        'as' => ['bahreïn', 'bahrain', 'dubaï', 'dubai', 'émirats', 'emirates', 'uae', 'japon', 'japan', 'chine', 'china', 'thaïlande', 'thailand', 'oman', 'qatar', 'inde', 'india', 'vietnam'],
        'oc' => ['australie', 'australia', 'nouvelle-zélande', 'new zealand'],
    ];
    foreach ($hints as $code => $words) {
        foreach ($words as $w) {
            if ($loc !== '' && strpos($loc, $w) !== false) {
                return $code;
            }
        }
    }
    return '';
}

/** Noms d'affichage : [FR, EN (vide si identique), est un Championnat du monde]. */
function pto_names(string $slug, string $year, string $name): array
{
    if ($slug === 'im-hawaii' || $slug === 'ironman-nice-world-championships') {
        return ['Championnat du monde IRONMAN', 'IRONMAN World Championship', true];
    }
    if ($slug === 'im-st-george-world-championships') {
        return ['Championnat du monde IRONMAN (édition 2021)', 'IRONMAN World Championship (2021 edition)', true];
    }
    if ($slug === 'alpe-dhuez-l') {
        return ["Triathlon de l'Alpe d'Huez L", "Alpe d'Huez Triathlon L", false];
    }
    $n = (string) preg_replace('/^Ironman\b/i', 'IRONMAN', $name);
    $n = str_replace("Sables D'Olonne", "Sables-d'Olonne", $n);
    return [$n, '', false];
}

/** Ligne du palmarès pour une course PTO qui n'y figure pas encore. */
function pto_new_row(array $p): array
{
    [$slug]       = explode('/', $p['pto'], 2);
    [$fr, $en, $w] = pto_names($slug, $p['year'], $p['name']);
    $loc = pto_locations()[$slug] ?? ['', ''];
    return [
        'year' => $p['year'], 'date' => $p['date'],
        'race' => $fr, 'race_en' => $en,
        'location' => $loc[0], 'location_en' => $loc[1],
        'rank' => $p['rank'], 'time' => $p['time'],
        'note_fr' => '', 'note_en' => '',
        'worlds' => $w ? '1' : '0', 'visible' => '1', 'pto' => $p['pto'],
    ];
}

/** Année d'une course : celle de la date exacte si elle existe, sinon le champ « année ». */
function results_year(array $r): int
{
    $fromDate = (int) substr((string) ($r['date'] ?? ''), 0, 4);
    return $fromDate > 0 ? $fromDate : (int) ($r['year'] ?? 0);
}

/** Tri du palmarès : année puis date, du plus récent au plus ancien (sans date = en fin d'année). */
function results_sort(array $rows): array
{
    foreach ($rows as $i => &$r) {
        $r['_i'] = $i;
    }
    unset($r);
    usort($rows, static function (array $a, array $b): int {
        $d = results_year($b) - results_year($a);
        if ($d !== 0) {
            return $d;
        }
        $c = strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        return $c !== 0 ? $c : $a['_i'] - $b['_i'];
    });
    foreach ($rows as &$r) {
        unset($r['_i']);
    }
    unset($r);
    return $rows;
}

/**
 * Fusionne les courses lues sur PTO dans le palmarès existant.
 * Course déjà connue (même clé « pto ») : place, temps et date sont mis à jour, le reste est conservé.
 * Nouvelle course : ajoutée, affichée par défaut. Retourne [palmarès trié, ajoutées, mises à jour].
 */
function pto_merge(array $existing, array $imported): array
{
    $byKey = [];
    foreach ($existing as $i => $r) {
        if (!empty($r['pto'])) {
            $byKey[(string) $r['pto']] = $i;
        }
    }
    $added = 0;
    $upd   = 0;
    foreach ($imported as $p) {
        if (isset($byKey[$p['pto']])) {
            $i = $byKey[$p['pto']];
            $changed = false;
            foreach (['rank', 'time', 'date', 'year'] as $f) {
                if ((string) ($existing[$i][$f] ?? '') !== (string) $p[$f]) {
                    $existing[$i][$f] = $p[$f];
                    $changed = true;
                }
            }
            $upd += $changed ? 1 : 0;
        } else {
            $existing[]        = pto_new_row($p);
            $byKey[$p['pto']]  = count($existing) - 1;
            $added++;
        }
    }
    return [results_sort($existing), $added, $upd];
}
