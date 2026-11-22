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

/* Calendrier : uniquement les courses à venir, triées par date. */
$upcoming = upcoming_events($c);

/* ---------- Textes d'interface ---------- */
$T = [
    'fr' => [
        'title'        => 'Arnaud Guilloux — Triathlète professionnel longue distance · Coaching, tests, bike fitting',
        'description'  => 'Arnaud Guilloux, triathlète professionnel longue distance (Nissa Triathlon), basé à Saint-Renan. Coaching, tests physiologiques, bike fitting et partenariats.',
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
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Devenir Partenaire',
        'place'        => 'Saint-Renan, Bretagne',
        'h_results'    => 'Palmarès',
        'h_partners'   => 'Partenaires',
        'pitch_title'  => 'Associez votre marque à la longue distance.',
        'pitch_text'   => 'Un athlète professionnel ancré en Bretagne et présent sur le circuit international. Visibilité en course, contenus, tests de matériel, interventions en entreprise : construisons un partenariat à votre mesure.',
        'pitch_points' => ['Visibilité en course et sur les réseaux', 'Retours terrain et tests de matériel', 'Interventions et sorties avec vos équipes'],
        'h_calendar'   => 'Calendrier',
        'legal'        => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.',
    ],
    'en' => [
        'title'        => 'Arnaud Guilloux — Professional long-distance triathlete · Coaching, testing, bike fitting',
        'description'  => 'Arnaud Guilloux, professional long-distance triathlete (Nissa Triathlon), based in Saint-Renan, Brittany. Coaching, physiological testing, bike fitting and partnerships.',
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
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Become a Partner',
        'place'        => 'Saint-Renan, Brittany',
        'h_results'    => 'Results',
        'h_partners'   => 'Partners',
        'pitch_title'  => 'Put your brand on the long-distance stage.',
        'pitch_text'   => 'A professional athlete rooted in Brittany and racing the international circuit. Race-day visibility, content, product testing, talks for your teams: let\'s build a partnership that fits you.',
        'pitch_points' => ['Visibility on race day and on social media', 'Field feedback and product testing', 'Talks and rides with your teams'],
        'h_calendar'   => 'Calendar',
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

$slides = array_slice(gallery_items('athlete'), 0, 5); // carrousel Highlights : 5 premières photos « athlète » (la galerie montre tout)
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=77">
<link rel="icon" href="<?= e($base) ?>assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="<?= e($base) ?>favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e($base) ?>assets/apple-touch-icon.png">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<?= site_header($lang, $home, $home, $c, '', true) ?>

<main>
<section class="hero">
  <div class="wrap">
    <div class="hero-panel">
      <img class="hero-bg" src="<?= e($base) ?>assets/img/roth-solarer-berg.jpg" width="2000" height="1333" alt="<?= e($T['alt_hero']) ?>" fetchpriority="high">
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


<?php if ($upcoming): ?>
<section id="calendrier">
  <div class="wrap">
    <h2><?= e($T['h_calendar']) ?></h2>
    <ol class="rows calendar">
      <?php foreach ($upcoming as $ev):
          [$y, $m, $d] = array_map('intval', explode('-', $ev['date'])); ?>
      <li>
        <time datetime="<?= e($ev['date']) ?>"><b><?= sprintf('%02d', $d) ?></b><span class="mono"><?= e($months[$lang][$m - 1] ?? '') ?> <?= $y ?></span></time>
        <span class="what">
          <strong><?= e(loc($ev, 'race')) ?></strong>
          <?php if (loc($ev, 'location') !== ''): ?><span class="where"><?= e(loc($ev, 'location')) ?></span><?php endif; ?>
        </span>
        <span class="note"><?= e(tr($ev, 'note')) ?></span>
        <?php if (($ev['format'] ?? '') !== ''): ?><span class="mono tag"><?= e($ev['format']) ?></span><?php else: ?><span></span><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
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
<script>
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Carrousels (Highlights) : fonction carousel() dans assets/carousel.js */
  carousel('scar', 'sdots', 'sprev', 'snext', '.slide');
})();
</script>
</body>
</html>
