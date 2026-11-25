<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';
require_once __DIR__ . '/lib_social.php';
require_once __DIR__ . '/lib_gallery.php';

/* ---------- Langue et URLs ---------- */
$uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$lang = (($_GET['lang'] ?? '') === 'en' || preg_match('#/en/?(\?|$)#', $uri)) ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$coachingUrl = $base . 'coaching.php' . ($lang === 'en' ? '?lang=en' : '');
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;

/* ---------- Contenu ---------- */
$c        = load_content();
$settings = $c['settings'] ?? [];
$hero     = $c['hero'] ?? [];
/* Photos du hero (diaporama) : fichier, largeur, hauteur, cadrage desktop, cadrage mobile, clé du texte alternatif */
$heroSlides = [
    ['hero-course-a-pied.jpg', 1333, 2000, '50% 38%', '55% 50%', 'alt_hero2'],
    ['roth-solarer-berg.jpg', 2000, 1333, '50% 32%', '47% 40%', 'alt_hero'],
    ['hero-velodrome.jpg', 2000, 1443, '50% 55%', '58% 50%', 'alt_hero3'],
];
$coaching = $c['coaching'] ?? [];
$draft    = ($settings['draft'] ?? '1') === '1';
$sent     = $_GET['sent'] ?? null;

/* Transforme la première mention du club (dans un texte déjà échappé) en lien vers le site du club. */
function club_link(string $html): string
{
    $name = 'Nissa Triathlon';
    $pos  = strpos($html, $name);
    if ($pos === false) {
        return $html;
    }
    return substr($html, 0, $pos)
        . '<a href="https://nissa-triathlon-team.org" target="_blank" rel="noopener">' . $name . '</a>'
        . substr($html, $pos + strlen($name));
}


/* Coaching : lignes d'offres */
$coachItems = coaching_items($c);

$months = [
    'fr' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
    'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
];

/* Calendrier : entrées à venir (courses, stages, événements). La prochaine course (à défaut, la prochaine entrée) passe en grand ; le reste est listé. */
$others = upcoming_events($c);
$nextEv   = null;
foreach ($others as $i => $ev) {
    if (event_type($ev) === 'race') {
        $nextEv = $ev;
        unset($others[$i]);
        break;
    }
}
if ($nextEv === null && $others) {
    $nextEv = array_shift($others);
}
$others = array_values($others);
/* Dates : [« 24 – 31 », « MAI 2027 »] ; sur deux mois : « 24 – 2 » / « MAI JUIN 2027 » ; sur deux ans : « DÉC 2027 JANV 2028 » */
$dateParts = static function (array $ev) use ($months, $lang): array {
    [$y, $m, $d] = array_map('intval', explode('-', $ev['date']));
    $end = event_end($ev);
    $mon = $months[$lang][$m - 1] ?? '';
    if ($end === $ev['date']) {
        return [sprintf('%02d', $d), $mon . ' ' . $y];
    }
    [$y2, $m2, $d2] = array_map('intval', explode('-', $end));
    $mon2 = $months[$lang][$m2 - 1] ?? '';
    if ($y2 !== $y) {
        $line = $mon . ' ' . $y . ' ' . $mon2 . ' ' . $y2;
    } elseif ($m2 !== $m) {
        $line = $mon . ' ' . $mon2 . ' ' . $y;
    } else {
        $line = $mon . ' ' . $y;
    }
    return [$d . ' – ' . $d2, $line];
};
$dateText = static function (array $ev) use ($months, $lang): string {
    [$y, $m, $d] = array_map('intval', explode('-', $ev['date']));
    $mon = $months[$lang][$m - 1] ?? '';
    $end = event_end($ev);
    if ($end === $ev['date']) {
        return $d . ' ' . $mon . ' ' . $y;
    }
    [$y2, $m2, $d2] = array_map('intval', explode('-', $end));
    if ($y2 === $y && $m2 === $m) {
        return $d . '–' . $d2 . ' ' . $mon . ' ' . $y;
    }
    return $d . ' ' . $mon . ($y2 !== $y ? ' ' . $y : '') . ' – ' . $d2 . ' ' . ($months[$lang][$m2 - 1] ?? '') . ' ' . $y2;
};
$typeName = static function (array $ev) use ($lang): string {
    return event_types()[event_type($ev)][$lang === 'en' ? 1 : 0];
};

/* ---------- Textes d'interface ---------- */
$T = [
    'fr' => [
        'title'        => 'Arnaud Guilloux — Triathlète professionnel longue distance · Coaching, tests, bike fitting',
        'description'  => 'Arnaud Guilloux, triathlète professionnel longue distance (Nissa Triathlon), basé à Saint-Renan. Coaching, tests physiologiques, bike fitting et partenariats.',
        'h_story'      => 'Mon histoire',
        'story_big'    => ['Nageur, coureur, cycliste.', 'Puis les trois à la fois.'],
        'story_more'   => 'Lire la suite',
        'h_highlights' => 'Highlights',
        'see_gallery'  => 'Voir la Galerie',
        'show_all'     => 'Afficher le Palmarès',
        'see_more'     => 'Voir plus…',
        'new_tab'      => 'nouvel onglet',
        'worlds_big'   => ['Championnat du monde IRONMAN.', '%d fois au départ.'],
        'coaching_box' => 'Coaching – Orienté performance',
        'coaching_more' => 'Découvrir le Coaching',
        'worlds_badge' => 'WC',
        'prev'         => 'Précédent',
        'next'         => 'Suivant',
        'alt_hero'     => 'Arnaud Guilloux à vélo entre deux haies de spectateurs au Challenge Roth',
        'alt_hero2'    => 'Arnaud Guilloux en course à pied au bord de l\'océan',
        'alt_hero3'    => 'Arnaud Guilloux en position aéro sur un vélodrome',
        'hero_photo'   => 'Photo',
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Devenir Partenaire',
        'place'        => 'Saint-Renan, Bretagne',
        'h_results'    => 'Palmarès',
        'h_partners'   => 'Partenaires',
        'pitch_title'  => 'Associez votre marque à la longue distance.',
        'pitch_text'   => 'Un athlète professionnel ancré en Bretagne et présent sur le circuit international. Visibilité en course, contenus, tests de matériel, interventions en entreprise : construisons un partenariat à votre mesure.',
        'pitch_points' => ['Visibilité en course et sur les réseaux', 'Retours terrain et tests de matériel', 'Interventions et sorties avec vos équipes'],
        'h_calendar'   => 'Calendrier',
        'next_race'    => 'Prochaine course',
        'next_camp'    => 'Prochain stage',
        'next_event'   => 'Prochain événement',
        'in'           => 'Dans',
        'days'         => 'jours',
        'day'          => 'jour',
        'today'        => 'Aujourd\'hui',
        'ongoing'      => 'En cours',
        'also'         => 'Aussi au programme',
        'legal'        => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.',
    ],
    'en' => [
        'title'        => 'Arnaud Guilloux — Professional long-distance triathlete · Coaching, testing, bike fitting',
        'description'  => 'Arnaud Guilloux, professional long-distance triathlete (Nissa Triathlon), based in Saint-Renan, Brittany. Coaching, physiological testing, bike fitting and partnerships.',
        'h_story'      => 'My story',
        'story_big'    => ['Swimmer, runner, cyclist.', 'Then all three at once.'],
        'story_more'   => 'Read more',
        'h_highlights' => 'Highlights',
        'see_gallery'  => 'See the Gallery',
        'show_all'     => 'Show Results',
        'see_more'     => 'See more…',
        'new_tab'      => 'opens in a new tab',
        'worlds_big'   => ['IRONMAN World Championship.', '%d times on the start line.'],
        'coaching_box' => 'Coaching – Performance-driven',
        'coaching_more' => 'Discover Coaching',
        'worlds_badge' => 'WC',
        'prev'         => 'Previous',
        'next'         => 'Next',
        'alt_hero'     => 'Arnaud Guilloux riding between two walls of spectators at Challenge Roth',
        'alt_hero2'    => 'Arnaud Guilloux running along the ocean',
        'alt_hero3'    => 'Arnaud Guilloux in aero position on a velodrome',
        'hero_photo'   => 'Photo',
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Become a Partner',
        'place'        => 'Saint-Renan, Brittany',
        'h_results'    => 'Results',
        'h_partners'   => 'Partners',
        'pitch_title'  => 'Put your brand on the long-distance stage.',
        'pitch_text'   => 'A professional athlete rooted in Brittany and racing the international circuit. Race-day visibility, content, product testing, talks for your teams: let\'s build a partnership that fits you.',
        'pitch_points' => ['Visibility on race day and on social media', 'Field feedback and product testing', 'Talks and rides with your teams'],
        'h_calendar'   => 'Calendar',
        'next_race'    => 'Next race',
        'next_camp'    => 'Next camp',
        'next_event'   => 'Next event',
        'in'           => 'In',
        'days'         => 'days',
        'day'          => 'day',
        'today'        => 'Today',
        'ongoing'      => 'Ongoing',
        'also'         => 'Also on the calendar',
        'legal'        => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.',
    ],
][$lang];

$F = contact_strings($lang);

$socials = social_links($settings);

$jsonLd = [
    '@context'     => 'https://schema.org',
    '@type'        => 'Person',
    'name'         => 'Arnaud Guilloux',
    'jobTitle'     => $lang === 'fr' ? 'Triathlète professionnel' : 'Professional triathlete',
    'url'          => $origin . $home[$lang],
    'memberOf'     => ['@type' => 'SportsTeam', 'name' => 'Nissa Triathlon'],
    'homeLocation' => ['@type' => 'Place', 'name' => 'Saint-Renan, France'],
    'sameAs'       => array_values($socials),
];

$slides = array_slice(gallery_items($c, 'athlete'), 0, 5); // carrousel Highlights : 5 premières photos « athlète » (la galerie montre tout)
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($T['title']) ?></title>
<meta name="description" content="<?= e($T['description']) ?>">
<?php if ($draft): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="canonical" href="<?= e($origin . $home[$lang]) ?>">
<link rel="alternate" hreflang="fr" href="<?= e($origin . $home['fr']) ?>">
<link rel="alternate" hreflang="en" href="<?= e($origin . $home['en']) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($T['title']) ?>">
<meta property="og:description" content="<?= e($T['description']) ?>">
<meta property="og:image" content="<?= e($origin . $base) ?>assets/img/roth-solarer-berg.jpg">
<meta name="theme-color" content="#ffffff">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=93">
<link rel="icon" href="<?= e($base) ?>assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="<?= e($base) ?>favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e($base) ?>assets/apple-touch-icon.png">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<?= site_header($lang, $home, $home, $c, '', true) ?>

<main>
<section class="hero hero-fixed">
  <div class="hero-stage">
    <div class="hero-panel hero-tall">
      <?php foreach ($heroSlides as $i => $sl): ?>
      <img class="hero-bg hero-slide<?= $i === 0 ? ' is-on' : '' ?>" src="<?= e($base) ?>assets/img/<?= e($sl[0]) ?>" width="<?= (int) $sl[1] ?>" height="<?= (int) $sl[2] ?>" alt="<?= e($T[$sl[5]]) ?>" style="--pos: <?= e($sl[3]) ?>; --pos-m: <?= e($sl[4]) ?>"<?= $i === 0 ? ' fetchpriority="high"' : ' decoding="async"' ?>>
      <?php endforeach; ?>
      <div class="hero-dots" id="hero-dots" role="group" aria-label="Photos">
        <?php foreach ($heroSlides as $i => $sl): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" aria-label="<?= e($T['hero_photo'] . ' ' . ($i + 1)) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button><?php endforeach; ?>
      </div>
      <div class="hero-inner">
        <div class="hero-copy">
          <p class="mono kicker"><?= e(tr($hero, 'kicker')) ?></p>
          <h1>Arnaud Guilloux</h1>
          <p class="mono place">Nissa Triathlon · <?= e($T['place']) ?></p>
        </div>
        <div class="cta">
          <a class="btn" href="#contact" data-contact="sponsoring"><?= e($T['cta_sponsor']) ?></a>
          <a class="btn" href="#contact" data-contact="coaching"><?= e($T['cta_coaching']) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="wrap intro">
  <div class="intro-grid">
    <p class="lead"><?= club_link(e(tr($hero, 'lead'))) ?></p>
    <?php if (tr($coaching, 'intro') !== '' || $coachItems): ?>
    <aside class="intro-coach">
      <p class="mono eyebrow"><?= e($T['coaching_box']) ?></p>
      <?php if (tr($coaching, 'intro') !== ''): ?><p class="intro-coach-text"><?= e(tr($coaching, 'intro')) ?></p><?php endif; ?>
      <a class="more" href="<?= e($coachingUrl) ?>"><?= e($T['coaching_more']) ?></a>
    </aside>
    <?php endif; ?>
  </div>
  <?php if (!empty($c['stats'])): ?>
  <dl class="stats">
    <?php foreach ($c['stats'] as $st): ?>
    <div>
      <?php if (($ico = stat_icon_svg((string) ($st['icon'] ?? ''))) !== ''): ?><span class="stat-ico" aria-hidden="true"><?= $ico ?></span><?php endif; ?>
      <dd><?= e(tr($st, 'value') ?: ($st['value'] ?? '')) ?></dd>
      <dt><?= e(tr($st, 'label')) ?></dt>
    </div>
    <?php endforeach; ?>
  </dl>
  <?php endif; ?>
</div>

<?php
/* Mon histoire : étapes datées (back office → L'Athlète → Mon histoire). Les 4 premières sont visibles, la suite se déplie. */
$story = array_values(array_filter(is_array($c['story'] ?? null) ? $c['story'] : [], static function ($s): bool {
    return is_array($s) && tr($s, 'title') !== '';
}));
$storyLi = static function (array $s): string {
    return '<li><span class="story-year">' . e($s['year'] ?? '') . '</span><div><h3>' . e(tr($s, 'title')) . '</h3>'
        . (tr($s, 'text') !== '' ? '<p>' . e(tr($s, 'text')) . '</p>' : '') . '</div></li>';
};
$storyFirst = 4; ?>
<?php if ($story): ?>
<section id="histoire">
  <div class="wrap">
    <p class="eyebrow"><?= e($T['h_story']) ?></p>
    <h2 class="big"><?= e($T['story_big'][0]) ?> <span><?= e($T['story_big'][1]) ?></span></h2>
    <ol class="story-list">
      <?php foreach (array_slice($story, 0, $storyFirst) as $s): ?>
      <?= $storyLi($s) . "\n" ?>
      <?php endforeach; ?>
    </ol>
    <?php if (count($story) > $storyFirst): ?>
    <details class="story-more">
      <summary><?= e($T['story_more']) ?></summary>
      <ol class="story-list">
        <?php foreach (array_slice($story, $storyFirst) as $s): ?>
        <?= $storyLi($s) . "\n" ?>
        <?php endforeach; ?>
      </ol>
    </details>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?= highlights_section_html($slides, $lang, $base, $T['h_highlights'], $base . 'galerie.php?type=athlete' . ($lang === 'en' ? '&lang=en' : ''), $T['see_gallery'], $T['prev'], $T['next'], 's') ?>

<?php
$rows      = results_rows($c);
$firstRows = 10;
$resultsUrl = $base . 'palmares.php' . ($lang === 'en' ? '?lang=en' : ''); ?>
<?php if ($rows): ?>
<section id="palmares">
  <div class="wrap">
    <div class="head-row">
      <h2><?= e($T['h_results']) ?></h2>
      <?php if (count($rows) > $firstRows): ?>
      <p class="links"><a class="more" href="<?= e($resultsUrl) ?>"><?= e($T['show_all']) ?> (<?= count($rows) ?>)</a></p>
      <?php endif; ?>
    </div>
    <ol class="rows results">
      <?php foreach (array_slice($rows, 0, $firstRows) as $r): ?>
      <?= result_li($r, rtrim($T['worlds_big'][0], '.'), $T['worlds_badge']) . "\n" ?>
      <?php endforeach; ?>
    </ol>
    <?php if (count($rows) > $firstRows): ?>
    <a class="see-more" href="<?= e($resultsUrl) ?>"><?= e($T['see_more']) ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>


<?php if ($nextEv):
    $hType = event_type($nextEv);
    $hDays = event_days_until($nextEv);
    [$hDay, $hMonth] = $dateParts($nextEv); ?>
<section id="calendrier">
  <div class="wrap">
    <h2><?= e($T['h_calendar']) ?></h2>
    <article class="next-event">
      <div class="next-main">
        <p class="mono next-kicker"><?= e($T['next_' . $hType]) ?></p>
        <h3><?= e(loc($nextEv, 'race')) ?></h3>
        <p class="next-when"><time datetime="<?= e($nextEv['date']) ?>"><?= e($dateText($nextEv)) ?></time></p>
        <?php if (loc($nextEv, 'location') !== '' || ($nextEv['format'] ?? '') !== '' || $hType !== 'race'): ?>
        <p class="next-meta">
          <?php if (loc($nextEv, 'location') !== ''): ?><span class="where"><?= e(loc($nextEv, 'location')) ?></span><?php endif; ?>
          <?php if ($hType !== 'race'): ?><span class="mono tag tag-<?= $hType ?>"><?= e($typeName($nextEv)) ?></span><?php endif; ?>
          <?php if (($nextEv['format'] ?? '') !== ''): ?><span class="mono tag"><?= e($nextEv['format']) ?></span><?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if (tr($nextEv, 'note') !== ''): ?><p class="note"><?= e(tr($nextEv, 'note')) ?></p><?php endif; ?>
      </div>
      <?php if ($hDays > 0): ?>
      <div class="next-count" aria-label="<?= e($T['in'] . ' ' . $hDays . ' ' . ($hDays > 1 ? $T['days'] : $T['day'])) ?>">
        <span class="mono"><?= e($T['in']) ?></span><b><?= $hDays ?></b><span class="mono"><?= e($hDays > 1 ? $T['days'] : $T['day']) ?></span>
      </div>
      <?php else: ?>
      <div class="next-count now"><b><?= e($hDays === 0 ? $T['today'] : $T['ongoing']) ?></b></div>
      <?php endif; ?>
    </article>
    <?php if ($others): ?>
    <h3 class="mono cal-sub"><?= e($T['also']) ?></h3>
    <ol class="rows calendar">
      <?php foreach ($others as $ev):
          $evType = event_type($ev);
          [$d1, $m1] = $dateParts($ev); ?>
      <li>
        <time datetime="<?= e($ev['date']) ?>"><b><?= e($d1) ?></b><span class="mono"><?= e($m1) ?></span></time>
        <span class="what">
          <strong><?= e(loc($ev, 'race')) ?></strong>
          <?php if (loc($ev, 'location') !== ''): ?><span class="where"><?= e(loc($ev, 'location')) ?></span><?php endif; ?>
        </span>
        <span class="note"><?= e(tr($ev, 'note')) ?></span>
        <span class="tags">
          <?php if ($evType !== 'race'): ?><span class="mono tag tag-<?= $evType ?>"><?= e($typeName($ev)) ?></span><?php endif; ?>
          <?php if (($ev['format'] ?? '') !== ''): ?><span class="mono tag"><?= e($ev['format']) ?></span><?php endif; ?>
        </span>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section id="partenaires">
  <div class="wrap">
    <p class="eyebrow"><?= e($T['h_partners']) ?></p>
    <h2 class="big"><?= e($T['pitch_title']) ?></h2>
    <div class="pitch">
      <div>
        <p class="feature-text"><?= e($T['pitch_text']) ?></p>
        <ul>
          <?php foreach ($T['pitch_points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?>
        </ul>
        <a class="btn" href="#contact"><?= e($T['cta_sponsor']) ?></a>
      </div>
      <?php if (!empty($c['partners'])): ?>
      <ul class="partners">
        <?php foreach ($c['partners'] as $p):
            $purl = trim((string) ($p['url'] ?? ''));
            $ok   = (bool) preg_match('#^https?://#i', $purl); ?>
        <?php $logo = trim((string) ($p['logo'] ?? ''));
              $hasLogo = $logo !== '' && preg_match('#^assets/img/partners/[A-Za-z0-9._-]+$#', $logo) && is_file(__DIR__ . '/' . $logo); ?>
        <li<?= $hasLogo ? ' class="has-logo"' : '' ?>>
          <?php if ($ok): ?><a href="<?= e($purl) ?>" rel="noopener noreferrer" target="_blank" aria-label="<?= e(($p['name'] ?? '') . ' — ' . $T['new_tab']) ?>"><?php endif; ?>
          <?php if ($hasLogo): ?><span class="p-logo"><img src="<?= e($base . $logo) ?>" alt="" loading="lazy"></span><?php endif; ?>
          <span class="p-text">
            <strong><?= e($p['name'] ?? '') ?><?php if ($ok): ?><?= ext_icon_svg() ?><?php endif; ?></strong>
            <span class="mono"><?= e(tr($p, 'role')) ?></span>
          </span>
          <?php if ($ok): ?></a><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<?= contact_section_html($F, $lang, $base, $sent === null ? null : (string) $sent, $socials, 'sponsoring') ?>
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>

<?= contact_dialog_html($F, $lang, $base) ?>

<script src="<?= e($base) ?>assets/carousel.js?v=2"></script>
<script src="<?= e($base) ?>assets/hero.js?v=2"></script>
<script>
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Carrousels (Highlights) : fonction carousel() dans assets/carousel.js */
  carousel('scar', 'sdots', 'sprev', 'snext', '.slide');
})();
</script>
</body>
</html>
