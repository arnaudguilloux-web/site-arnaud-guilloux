<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';
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
$email    = trim((string) ($settings['contact_email'] ?? ''));
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


/* Coaching : lignes d'offres. Témoignages : seulement ceux qui ont une citation. */
$coachItems = coaching_items($c);
$quotes = testimonials($c);

$months = [
    'fr' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
    'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
];

/* Calendrier : uniquement les courses à venir, triées par date. */
$today    = date('Y-m-d');
$upcoming = array_values(array_filter($c['calendar'] ?? [], static function ($ev) use ($today) {
    return is_array($ev) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($ev['date'] ?? '')) && $ev['date'] >= $today;
}));
usort($upcoming, static function ($a, $b) {
    return strcmp($a['date'], $b['date']);
});

/* ---------- Textes d'interface ---------- */
$T = [
    'fr' => [
        'title'        => 'Arnaud Guilloux — Triathlète professionnel longue distance · Coaching, tests, bike fitting',
        'description'  => 'Arnaud Guilloux, triathlète professionnel longue distance (Nissa Triathlon), basé à Saint-Renan. Coaching, tests physiologiques, bike fitting et partenariats.',
        'h_highlights' => 'Highlights',
        'see_gallery'  => 'Voir la galerie',
        'see_results'  => 'Voir le palmarès',
        'see_worlds'   => 'Les Mondiaux',
        'show_all'     => 'Afficher tout le palmarès',
        'see_more'     => 'Voir plus…',
        'new_tab'      => 'nouvel onglet',
        'worlds_big'   => ['Championnat du monde IRONMAN.', '%d fois au départ.'],
        'h_coaching'   => 'Coaching',
        'coaching_big' => ['Le coaching.', 'Orienté performance.'],
        'coaching_box' => 'Coaching – Orienté performance',
        'coaching_cta' => 'Parler de votre objectif',
        'coaching_more' => 'Découvrir le coaching',
        'worlds_badge' => 'WC',
        'h_quotes'     => 'Témoignages',
        'quotes_big'   => ['Ce que disent', 'mes athlètes.'],
        'quotes_note'  => '',
        'prev'         => 'Précédent',
        'next'         => 'Suivant',
        'alt_hero'     => 'Arnaud Guilloux à vélo entre deux haies de spectateurs au Challenge Roth',
        'nav'          => ['palmares' => 'Palmarès', 'coaching' => 'Coaching', 'partenaires' => 'Partenaires', 'calendrier' => 'Calendrier', 'contact' => 'Contact'],
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Devenir partenaire',
        'place'        => 'Saint-Renan, Bretagne',
        'h_results'    => 'Palmarès',
        'h_partners'   => 'Partenaires',
        'pitch_title'  => 'Associez votre marque à la longue distance.',
        'pitch_text'   => 'Un athlète professionnel ancré en Bretagne et présent sur le circuit international. Visibilité en course, contenus, tests de matériel, interventions en entreprise : construisons un partenariat à votre mesure.',
        'pitch_points' => ['Visibilité en course et sur les réseaux', 'Retours terrain et tests de matériel', 'Interventions et sorties avec vos équipes'],
        'pitch_cta'    => 'Parler d\'un partenariat',
        'h_calendar'   => 'Calendrier',
        'h_contact'    => 'Contact',
        'contact_lead' => 'Un projet sportif, une prestation à réserver ou un partenariat à imaginer : écrivez-moi.',
        'f_name'       => 'Nom',
        'f_email'      => 'E-mail',
        'f_topic'      => 'Sujet',
        'topics'       => ['coaching' => 'Coaching, tests, bike fitting', 'sponsoring' => 'Partenariat / sponsoring', 'other' => 'Autre'],
        'f_message'    => 'Message',
        'f_send'       => 'Envoyer',
        'sent_ok'      => 'Merci, votre message est bien parti. Je vous réponds rapidement.',
        'sent_ko'      => 'Le message n\'a pas pu être envoyé. Vérifiez les champs ou écrivez-moi directement.',
        'legal'        => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.',
    ],
    'en' => [
        'title'        => 'Arnaud Guilloux — Professional long-distance triathlete · Coaching, testing, bike fitting',
        'description'  => 'Arnaud Guilloux, professional long-distance triathlete (Nissa Triathlon), based in Saint-Renan, Brittany. Coaching, physiological testing, bike fitting and partnerships.',
        'h_highlights' => 'Highlights',
        'see_gallery'  => 'See the gallery',
        'see_results'  => 'See the results',
        'see_worlds'   => 'World Championships',
        'show_all'     => 'Show all results',
        'see_more'     => 'See more…',
        'new_tab'      => 'opens in a new tab',
        'worlds_big'   => ['IRONMAN World Championship.', '%d times on the start line.'],
        'h_coaching'   => 'Coaching',
        'coaching_big' => ['Coaching.', 'Performance-driven.'],
        'coaching_box' => 'Coaching – Performance-driven',
        'coaching_cta' => 'Talk about your goal',
        'coaching_more' => 'Discover coaching',
        'worlds_badge' => 'WC',
        'h_quotes'     => 'Testimonials',
        'quotes_big'   => ['What my', 'athletes say.'],
        'quotes_note'  => 'Testimonials are shown in their original French.',
        'prev'         => 'Previous',
        'next'         => 'Next',
        'alt_hero'     => 'Arnaud Guilloux riding between two walls of spectators at Challenge Roth',
        'nav'          => ['palmares' => 'Results', 'coaching' => 'Coaching', 'partenaires' => 'Partners', 'calendrier' => 'Calendar', 'contact' => 'Contact'],
        'cta_coaching' => 'Coaching',
        'cta_sponsor'  => 'Become a partner',
        'place'        => 'Saint-Renan, Brittany',
        'h_results'    => 'Results',
        'h_partners'   => 'Partners',
        'pitch_title'  => 'Put your brand on the long-distance stage.',
        'pitch_text'   => 'A professional athlete rooted in Brittany and racing the international circuit. Race-day visibility, content, product testing, talks for your teams: let\'s build a partnership that fits you.',
        'pitch_points' => ['Visibility on race day and on social media', 'Field feedback and product testing', 'Talks and rides with your teams'],
        'pitch_cta'    => 'Talk about a partnership',
        'h_calendar'   => 'Calendar',
        'h_contact'    => 'Contact',
        'contact_lead' => 'A sporting goal, a service to book or a partnership to shape: get in touch.',
        'f_name'       => 'Name',
        'f_email'      => 'Email',
        'f_topic'      => 'Topic',
        'topics'       => ['coaching' => 'Coaching, testing, bike fitting', 'sponsoring' => 'Partnership / sponsoring', 'other' => 'Other'],
        'f_message'    => 'Message',
        'f_send'       => 'Send',
        'sent_ok'      => 'Thank you, your message is on its way. I\'ll get back to you shortly.',
        'sent_ko'      => 'The message could not be sent. Check the fields or email me directly.',
        'legal'        => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.',
    ],
][$lang];

/* Pas de course à venir : on masque le lien du menu et la section Calendrier. */
if (!$upcoming) {
    unset($T['nav']['calendrier']);
}

$socials = [];
foreach (['instagram' => 'Instagram', 'strava' => 'Strava', 'facebook' => 'Facebook', 'x' => 'X', 'linkedin' => 'LinkedIn'] as $key => $label) {
    $url = trim((string) ($settings[$key] ?? ''));
    if (preg_match('#^https?://#i', $url)) {
        $socials[$label] = $url;
    }
}

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

$slides = array_slice(gallery_items(), 0, 5); // carrousel Highlights : 5 premières photos (la galerie montre tout)
$n = 0; // numérotation des sections
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=47">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<header class="top">
  <div class="wrap">
    <a class="brand" href="<?= e($home[$lang]) ?>">Arnaud <strong>Guilloux</strong></a>
    <nav class="nav" aria-label="Sections">
      <?php foreach ($T['nav'] as $id => $label): ?>
      <a href="<?= $id === 'coaching' ? e($coachingUrl) : '#' . $id ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="lang" aria-label="Langue / Language">
      <a href="<?= e($home['fr']) ?>" hreflang="fr" lang="fr"<?= $lang === 'fr' ? ' aria-current="true"' : '' ?>>FR</a>
      <a href="<?= e($home['en']) ?>" hreflang="en" lang="en"<?= $lang === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
    </div>
  </div>
</header>

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
          <a class="btn" href="<?= e($coachingUrl) ?>"><?= e($T['cta_coaching']) ?></a>
          <a class="btn" href="#partenaires"><?= e($T['cta_sponsor']) ?></a>
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

<section class="highlights">
  <div class="wrap">
    <div class="head-row">
      <h2><?= e($T['h_highlights']) ?></h2>
      <p class="links">
        <a class="more" href="<?= e($base) ?>galerie.php<?= $lang === 'en' ? '?lang=en' : '' ?>"><?= e($T['see_gallery']) ?></a>
      </p>
    </div>
  </div>
  <div class="carousel scar" id="scar" tabindex="0" aria-label="<?= e($T['h_highlights']) ?>">
    <?php foreach ($slides as $sl): ?>
    <figure class="slide">
      <img src="<?= e($base) ?>assets/img/<?= e($sl[0]) ?>.jpg" alt="" loading="lazy">
      <figcaption><strong><?= e($sl[$lang === 'en' ? 3 : 1]) ?></strong><span><?= e($sl[$lang === 'en' ? 4 : 2]) ?></span></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="wrap">
    <div class="qctrl">
      <button type="button" class="arrow" id="sprev" aria-label="<?= e($T['prev']) ?>">‹</button>
      <div class="dots" id="sdots" aria-hidden="true">
        <?php foreach ($slides as $i => $sl): ?><span<?= $i === 0 ? ' class="on"' : '' ?>></span><?php endforeach; ?>
      </div>
      <button type="button" class="arrow" id="snext" aria-label="<?= e($T['next']) ?>">›</button>
    </div>
  </div>
</section>

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

<?php if (tr($coaching, 'intro') !== '' || $coachItems): ?>
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
        <a class="btn" href="#contact"><?= e($T['pitch_cta']) ?></a>
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

<section id="contact">
  <div class="wrap contact">
    <div>
      <h2><?= e($T['h_contact']) ?></h2>
      <p class="feature-text"><?= e($T['contact_lead']) ?></p>
      <?php if ($socials): ?>
      <ul class="socials">
        <?php foreach ($socials as $label => $url): ?><li><a class="more" href="<?= e($url) ?>" rel="noopener me" target="_blank"><?= e($label) ?></a></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <form class="card" method="post" action="<?= e($base) ?>contact.php">
      <?php if ($sent === '1'): ?><p class="flash" role="status"><?= e($T['sent_ok']) ?></p>
      <?php elseif ($sent === '0'): ?><p class="flash" role="alert"><?= e($T['sent_ko']) ?></p><?php endif; ?>
      <input type="hidden" name="lang" value="<?= $lang ?>">
      <input type="hidden" name="ts" value="<?= time() ?>">
      <p class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
      <label><span><?= e($T['f_name']) ?></span><input type="text" name="name" required maxlength="120" autocomplete="name"></label>
      <label><span><?= e($T['f_email']) ?></span><input type="email" name="email" required maxlength="160" autocomplete="email"></label>
      <label><span><?= e($T['f_topic']) ?></span>
        <select name="topic">
          <?php foreach ($T['topics'] as $val => $label): ?><option value="<?= $val ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label><span><?= e($T['f_message']) ?></span><textarea name="message" rows="5" required maxlength="5000"></textarea></label>
      <button class="btn" type="submit"><?= e($T['f_send']) ?></button>
    </form>
  </div>
</section>
</main>

<footer class="foot">
  <div class="wrap">
    <p><?= e($T['legal']) ?><?php if (trim((string) ($settings['siret'] ?? '')) !== ''): ?> SIRET <?= e($settings['siret']) ?>.<?php endif; ?></p>
    <p>© <?= date('Y') ?> Arnaud Guilloux</p>
  </div>
</footer>

<script src="<?= e($base) ?>assets/carousel.js?v=1"></script>
<script>
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Menu : entrée active selon la section visible */
  var navLinks = Array.prototype.slice.call(document.querySelectorAll('.nav a[href^="#"]'));
  var navTargets = navLinks.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
  function spy() {
    var cur = -1;
    navTargets.forEach(function (el, i) { if (el && el.getBoundingClientRect().top <= 120) cur = i; });
    navLinks.forEach(function (a, i) { a.classList.toggle('on', i === cur); });
  }
  addEventListener('scroll', spy, { passive: true });
  addEventListener('resize', spy);
  spy();

  /* Carrousels (Highlights) : fonction carousel() dans assets/carousel.js */
  carousel('scar', 'sdots', 'sprev', 'snext', '.slide');
})();
</script>
</body>
</html>
