<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_social.php';

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
        $mod = ['IRONMAN' => 'im', 'IRONMAN 70.3' => 'im70', 'Challenge' => 'ch'][$kind] ?? '';
        $h .= '<span class="kind' . ($mod !== '' ? ' kind-' . $mod : '') . '">' . e($kind) . '</span>';
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

/** Carte de témoignage (page Coaching et page « Tous les témoignages »). Le prénom est un lien facultatif (fiche Triathlon Lab). */
function testimonial_card_html(array $q, string $lang): string
{
    $name = trim((string) ($q['name'] ?? ''));
    $city = trim((string) ($q['city'] ?? ''));
    $hl   = tr($q, 'highlight');
    $ctx  = tr($q, 'context');
    $h    = '<figure class="qcard">';
    if ($hl !== '') {
        $h .= '<div class="q-hl"><strong>' . e($hl) . '</strong>' . ($ctx !== '' ? '<span class="mono">' . e($ctx) . '</span>' : '') . '</div>';
    }
    $h .= '<blockquote lang="fr"><p>«&nbsp;' . e(trim((string) ($q['quote'] ?? ''))) . '&nbsp;»</p></blockquote>';
    if ($name !== '') {
        $url = trim((string) ($q['url'] ?? ''));
        if (preg_match('#^https?://#i', $url)) {
            $label = $name . ' — ' . ($lang === 'en' ? 'Triathlon Lab profile, opens in a new tab' : 'fiche Triathlon Lab, nouvel onglet');
            $who   = '<a href="' . e($url) . '" rel="noopener noreferrer" target="_blank" aria-label="' . e($label) . '">' . e($name) . ext_icon_svg() . '</a>';
        } else {
            $who = e($name);
        }
        $h .= '<figcaption class="mono">' . $who . ($city !== '' ? ' – ' . e($city) : '') . '</figcaption>';
    }
    return $h . '</figure>';
}

/**
 * Section « Highlights » : carrousel de photos + lien « Voir la Galerie » (accueil et page Coach).
 * $slides : lignes de gallery_items() ; $p : préfixe des ids du carrousel (ex. « s » → scar, sdots, sprev, snext).
 */
function highlights_section_html(array $slides, string $lang, string $base, string $title, string $moreHref, string $moreLabel, string $prev, string $next, string $p = 's'): string
{
    if (!$slides) {
        return '';
    }
    $en = $lang === 'en';
    $h  = '<section class="highlights" id="highlights"><div class="wrap"><div class="head-row"><h2>' . e($title) . '</h2>';
    $h .= '<p class="links"><a class="more" href="' . e($moreHref) . '">' . e($moreLabel) . '</a></p></div></div>';
    $h .= '<div class="carousel scar" id="' . $p . 'car" tabindex="0" aria-label="' . e($title) . '">';
    foreach ($slides as $sl) {
        $focus = isset($sl[6]) && preg_match('/^\d{1,3}% \d{1,3}%$/', (string) $sl[6]) ? ' style="object-position: ' . $sl[6] . '"' : '';
        $h .= '<figure class="slide"><img src="' . e($base) . 'assets/img/' . e($sl[0]) . '.jpg" alt=""' . $focus . ' loading="lazy">'
            . '<figcaption><strong>' . e($sl[$en ? 3 : 1]) . '</strong><span>' . e($sl[$en ? 4 : 2]) . '</span></figcaption></figure>';
    }
    $h .= '</div><div class="wrap"><div class="qctrl">'
        . '<button type="button" class="arrow" id="' . $p . 'prev" aria-label="' . e($prev) . '">‹</button>'
        . '<div class="dots" id="' . $p . 'dots" aria-hidden="true">';
    foreach ($slides as $i => $_) {
        $h .= '<span' . ($i === 0 ? ' class="on"' : '') . '></span>';
    }
    $h .= '</div><button type="button" class="arrow" id="' . $p . 'next" aria-label="' . e($next) . '">›</button></div></div></section>';
    return $h;
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
 * Menu : « L'Athlète » et « Le Coach » (sous-menus d'ancres vers les sections), « Mes Annonces » (lien externe).
 * $current : 'highlights' | 'palmares' | 'coaching' | 'temoignages' | '' (page courante, mise en avant dans le menu).
 * $onHome  : sur l'accueil, les liens vers la home sont des ancres (#id) ; sur la page Coaching, ceux du Coach aussi.
 */
function site_header(string $lang, array $home, array $self, array $c, string $current = '', bool $onHome = false): string
{
    $en = $lang === 'en';
    $T  = $en
        ? ['athlete' => 'The Athlete', 'coach' => 'The Coach', 'ads' => 'My Listings', 'new_tab' => 'opens in a new tab',
           'athlete_items' => [
               'top'         => ['Overview', 'Professional long-distance triathlete'],
               'highlights'  => ['Highlights', 'Photos from the big races'],
               'palmares'    => ['Results', 'The full race record'],
               'partenaires' => ['Partners', 'Those who back me'],
               'contact'     => ['Contact', 'A question, a project'],
           ],
           'coach_items' => [
               'top'         => ['Overview', 'Performance-driven'],
               'offres'      => ['Offers', 'Plans, camps, testing, bike fitting'],
               'temoignages' => ['Testimonials', 'What my athletes say'],
               'contact'     => ['Contact', 'A goal? Let\'s talk'],
           ]]
        : ['athlete' => "L'Athlète", 'coach' => 'Le Coach', 'ads' => 'Mes Annonces', 'new_tab' => 'nouvel onglet',
           'athlete_items' => [
               'top'         => ['Présentation', 'Triathlète professionnel longue distance'],
               'highlights'  => ['Highlights', 'Photos des grandes courses'],
               'palmares'    => ['Palmarès', 'Tous les résultats'],
               'partenaires' => ['Partenaires', 'Ceux qui m\'accompagnent'],
               'contact'     => ['Contact', 'Une question, un projet'],
           ],
           'coach_items' => [
               'top'         => ['Présentation', 'Orienté performance'],
               'offres'      => ['Offres', 'Plans, stages, tests, bike fitting'],
               'temoignages' => ['Témoignages', 'Ce que disent mes athlètes'],
               'contact'     => ['Contact', 'Un objectif ? Parlons-en'],
           ]];

    /* Lien externe « Mes Annonces » : réglable dans l'admin (Réglages), sinon page d'accueil Troc-Vélo. */
    $set    = $c['settings'] ?? [];
    $adsUrl = trim((string) ($set['ads_url_' . $lang] ?? ''));
    if (!preg_match('#^https?://#i', $adsUrl)) {
        $adsUrl = $en ? 'https://www.troc-velo.com/en' : 'https://www.troc-velo.com/fr-fr';
    }

    $suffix   = $en ? '?lang=en' : '';
    $base     = $home['fr'];
    $coachUrl = $base . 'coaching.php' . $suffix;
    /* Pages qui prolongent une section de l'Athlète : le lien du sous-menu y pointe quand on est dessus. */
    $pages    = ['highlights' => 'galerie.php', 'palmares' => 'palmares.php'];
    $onCoach  = $current === 'coaching';                       /* sur la page Coaching : liens du Coach en ancres */
    $inCoach  = $onCoach || $current === 'temoignages';        /* dans la partie Coach (déroule le titre « Le Coach ») */

    $group = static function (string $key, string $label, string $href, bool $isCurrent, string $items): string {
        return '<div class="has-sub">'
            . '<a class="nav-trigger' . ($isCurrent ? ' on' : '') . '" href="' . e($href) . '" data-page-on="' . ($isCurrent ? '1' : '0') . '" aria-haspopup="true" aria-expanded="false" aria-controls="sub-' . $key . '">'
            . e($label)
            . '<svg class="chev" viewBox="0 0 12 8" width="10" height="7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 1.5l5 5 5-5"/></svg></a>'
            . '<div class="sub" id="sub-' . $key . '"><ul>' . $items . '</ul></div></div>';
    };

    /* L'Athlète */
    $a = '';
    foreach ($T['athlete_items'] as $id => [$label, $hint]) {
        if ($id === 'top') {
            $href = $onHome ? '#' : $home[$lang];   /* haut de la page d'accueil */
        } else {
            $href = ($id === $current && isset($pages[$id])) ? $base . $pages[$id] . $suffix : (($onHome ? '' : $home[$lang]) . '#' . $id);
        }
        $on   = $id === $current ? ' class="on" aria-current="page"' : '';
        if ($id === 'top' && $onHome) { $on .= ' data-top="1"'; }   /* actif tant qu'on est en haut de page (scroll-spy) */
        $a   .= '<li><a href="' . e($href) . '"' . $on . '><strong>' . e($label) . '</strong><span>' . e($hint) . '</span></a></li>';
    }

    /* Le Coach */
    $k = '';
    foreach ($T['coach_items'] as $id => [$label, $hint]) {
        $href = $id === 'top' ? $coachUrl : (($onCoach ? '' : $coachUrl) . '#' . $id);
        $top  = ($id === 'top' && $onCoach) ? ' data-top="1"' : '';
        if ($id === 'temoignages' && $current === 'temoignages') {   /* page « Tous les témoignages » : l'entrée pointe sur elle-même */
            $href = $base . 'temoignages.php' . $suffix;
            $top  = ' class="on" aria-current="page"';
        }
        $k   .= '<li><a href="' . e($href) . '"' . $top . '><strong>' . e($label) . '</strong><span>' . e($hint) . '</span></a></li>';
    }

    $h  = '<header class="top"><div class="wrap">';
    $h .= '<a class="brand" href="' . e($home[$lang]) . '">Arnaud <strong>Guilloux</strong></a>';
    $h .= '<nav class="nav" aria-label="Sections">';
    /* Sur ordinateur, cliquer le titre ouvre la page ; le sous-menu s'ouvre au survol. Sur mobile / tactile, le clic déplie le sous-menu. */
    $h .= $group('athlete', $T['athlete'], $onHome ? '#' : $home[$lang], isset($T['athlete_items'][$current]), $a);
    $h .= $group('coach', $T['coach'], $coachUrl, $inCoach, $k);
    $h .= '<a href="' . e($adsUrl) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($T['ads'] . ' — ' . $T['new_tab']) . '">' . e($T['ads']) . ext_icon_svg() . '</a>';
    $h .= '</nav>';

    $h .= '<div class="lang" aria-label="Langue / Language">';
    foreach (['fr' => 'FR', 'en' => 'EN'] as $code => $txt) {
        $dest = $onHome ? $home[$code] : $self[$code];
        $h .= '<a href="' . e($dest) . '" hreflang="' . $code . '" lang="' . $code . '"' . ($lang === $code ? ' aria-current="true"' : '') . '>' . $txt . '</a>';
    }
    $h .= '</div></div></header>';
    $h .= '<script src="' . e($base) . 'assets/nav.js?v=5" defer></script>';
    return $h;
}

/** Pied de page commun : mentions légales (+ SIRET si renseigné), réseaux sociaux et copyright. */
function site_footer(string $legal, array $settings, string $lang = 'fr'): string
{
    $siret = preg_replace('/\D/', '', (string) ($settings['siret'] ?? ''));
    if (strlen($siret) === 14) {
        $siret = substr($siret, 0, 3) . ' ' . substr($siret, 3, 3) . ' ' . substr($siret, 6, 3) . ' ' . substr($siret, 9);
    }
    $line = $legal . ($siret !== '' ? ' SIRET ' . $siret . '.' : '');
    $tab  = $lang === 'en' ? 'opens in a new tab' : 'nouvel onglet';

    $soc = '';
    foreach (social_links($settings) as $label => $url) {
        $soc .= '<li><a href="' . e($url) . '" rel="noopener me" target="_blank" aria-label="' . e($label . ' — ' . $tab) . '" title="' . e($label) . '">' . social_icon_svg($label) . '</a></li>';
    }

    return '<footer class="foot"><div class="wrap"><p>' . e($line) . '</p><div class="foot-right">'
        . ($soc !== '' ? '<ul class="foot-social">' . $soc . '</ul>' : '')
        . '<p>© ' . date('Y') . ' Arnaud Guilloux</p></div></div></footer>';
}

/** Textes du formulaire de contact et de la popin (partagés par l'accueil et la page Coaching). */
function contact_strings(string $lang): array
{
    return $lang === 'en'
        ? ['h_contact' => 'Contact', 'contact_lead' => 'A sporting goal, a service to book or a partnership to shape: get in touch.', 'f_name' => 'Name', 'f_email' => 'Email', 'f_topic' => 'Topic', 'f_message' => 'Message', 'f_send' => 'Send', 'f_close' => 'Close',
           'topics' => ['coaching' => 'Coaching, testing, bike fitting', 'sponsoring' => 'Partnership / sponsoring', 'other' => 'Other'],
           'sent_ok' => 'Thank you, your message is on its way. I\'ll get back to you shortly.',
           'sent_ko' => 'The message could not be sent. Check the fields or email me directly.']
        : ['h_contact' => 'Contact', 'contact_lead' => 'Un projet sportif, une prestation à réserver ou un partenariat à imaginer : écrivez-moi.', 'f_name' => 'Nom', 'f_email' => 'E-mail', 'f_topic' => 'Sujet', 'f_message' => 'Message', 'f_send' => 'Envoyer', 'f_close' => 'Fermer',
           'topics' => ['coaching' => 'Coaching, tests, bike fitting', 'sponsoring' => 'Partenariat / sponsoring', 'other' => 'Autre'],
           'sent_ok' => 'Merci, votre message est bien parti. Je vous réponds rapidement.',
           'sent_ko' => 'Le message n\'a pas pu être envoyé. Vérifiez les champs ou écrivez-moi directement.'];
}

/** Formulaire de contact (section Contact de l'accueil et popin). $withFlash : affiche le résultat d'un envoi sans JavaScript (?sent=). */
function contact_form_html(array $F, string $lang, string $base, ?string $sent, bool $withFlash, string $topic = ''): string
{
    $h = '<form class="card" method="post" action="' . e($base) . 'contact.php">';
    if ($withFlash && $sent === '1') {
        $h .= '<p class="flash" role="status">' . e($F['sent_ok']) . '</p>';
    } elseif ($withFlash && $sent === '0') {
        $h .= '<p class="flash" role="alert">' . e($F['sent_ko']) . '</p>';
    }
    $h .= '<input type="hidden" name="lang" value="' . e($lang) . '">';
    $h .= '<input type="hidden" name="ts" value="' . time() . '">';
    $h .= '<p class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>';
    $h .= '<label><span>' . e($F['f_name']) . '</span><input type="text" name="name" required maxlength="120" autocomplete="name"></label>';
    $h .= '<label><span>' . e($F['f_email']) . '</span><input type="email" name="email" required maxlength="160" autocomplete="email"></label>';
    $h .= '<label><span>' . e($F['f_topic']) . '</span><select name="topic">';
    foreach ($F['topics'] as $val => $label) {
        $h .= '<option value="' . e((string) $val) . '"' . ((string) $val === $topic ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    $h .= '</select></label>';
    $h .= '<label><span>' . e($F['f_message']) . '</span><textarea name="message" rows="5" required maxlength="5000"></textarea></label>';
    $h .= '<button class="btn" type="submit">' . e($F['f_send']) . '</button>';
    return $h . '</form>';
}

/** Popin de contact : s'ouvre depuis tout élément [data-contact="coaching|sponsoring|other"] (voir assets/contact.js). */
function contact_dialog_html(array $F, string $lang, string $base): string
{
    return '<dialog class="modal" id="contact-dialog" aria-labelledby="cd-title"><div class="modal-box">'
        . '<button class="modal-close" type="button" data-cd-close aria-label="' . e($F['f_close']) . '">'
        . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5L5 19"/></svg></button>'
        . '<h2 id="cd-title">' . e($F['h_contact']) . '</h2>'
        . '<p class="flash" data-cd-status hidden data-ok="' . e($F['sent_ok']) . '" data-ko="' . e($F['sent_ko']) . '"></p>'
        . contact_form_html($F, $lang, $base, null, false)
        . '</div></dialog>'
        . '<script src="' . e($base) . 'assets/contact.js?v=1" defer></script>';
}

/** Section Contact (identique sur l'accueil et la page Coaching) : texte, réseaux sociaux, formulaire. $topic : sujet présélectionné. */
function contact_section_html(array $F, string $lang, string $base, ?string $sent, array $socials, string $topic = ''): string
{
    $h = '<section id="contact"><div class="wrap contact"><div>'
       . '<h2>' . e($F['h_contact']) . '</h2>'
       . '<p class="feature-text">' . e($F['contact_lead']) . '</p>';
    if ($socials) {
        $h .= '<ul class="socials">';
        foreach ($socials as $label => $url) {
            $h .= '<li><a class="more" href="' . e($url) . '" rel="noopener me" target="_blank">' . e($label) . '</a></li>';
        }
        $h .= '</ul>';
    }
    return $h . '</div>' . contact_form_html($F, $lang, $base, $sent, true, $topic) . '</div></section>';
}

