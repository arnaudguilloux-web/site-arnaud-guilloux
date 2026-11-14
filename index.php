<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

/* ---------- Langue et URLs ---------- */
$uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$lang = (($_GET['lang'] ?? '') === 'en' || preg_match('#/en/?(\?|$)#', $uri)) ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;

/* ---------- Contenu ---------- */
$c        = load_content();
$settings = $c['settings'] ?? [];
$hero     = $c['hero'] ?? [];
$draft    = ($settings['draft'] ?? '1') === '1';
$email    = trim((string) ($settings['contact_email'] ?? ''));
$sent     = $_GET['sent'] ?? null;

function tr(array $item, string $key): string
{
    global $lang;
    $v = $item[$key . '_' . $lang] ?? '';
    return (string) ($v !== '' ? $v : ($item[$key . '_fr'] ?? ''));
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
        'draft'        => 'Site en préparation — contenu en cours de validation.',
        'on_request'   => 'Sur demande',
        'h_highlights' => 'Temps forts.',
        'see_results'  => 'Voir le palmarès',
        'see_worlds'   => 'Les Mondiaux',
        'show_all'     => 'Afficher tout le palmarès',
        'worlds_big'   => ['Championnat du monde IRONMAN.', '%d fois au départ.'],
        'services_big' => ['Votre préparation.', 'Mes outils de pro.'],
        'slides'       => [
            ['roth-arrivee', 'Challenge Roth 2026.', '7 h 46 min 30 sur la distance IRONMAN.'],
            ['brasil-podium', 'IRONMAN Brasil 2026.', 'Troisième en 7 h 38.'],
            ['roth-velo', '180 km à vélo.', 'Là où se construit la course.'],
            ['roth-course', 'Puis un marathon.', 'Jusqu\'à la ligne.'],
        ],
        'alt_hero'     => 'Arnaud Guilloux à vélo entre deux haies de spectateurs au Challenge Roth',
        'alt_kona'     => ['Arnaud Guilloux et son vélo sur un champ de lave à Hawaï', 'Arnaud Guilloux au dépôt des vélos du Championnat du monde IRONMAN'],
        'alt_portrait' => 'Arnaud Guilloux devant le phare du Petit Minou, en Bretagne',
        'nav'          => ['palmares' => 'Palmarès', 'mondiaux' => 'Mondiaux', 'prestations' => 'Prestations', 'partenaires' => 'Partenaires', 'calendrier' => 'Calendrier', 'contact' => 'Contact'],
        'cta_coaching' => 'Coaching & tests',
        'cta_sponsor'  => 'Devenir partenaire',
        'place'        => 'Saint-Renan, Bretagne',
        'h_results'    => 'Palmarès',
        'h_qualif'     => 'Qualifications aux Mondiaux',
        'qualif_lead'  => 'Se qualifier chez les professionnels, c\'est gagner sa place course après course.',
        'h_services'   => 'Prestations',
        'services_lead' => 'Ce que j\'applique à ma propre préparation, mis au service de la vôtre. À Saint-Renan ou à distance selon la prestation.',
        'from'         => 'À partir de',
        'ask'          => 'Demander un créneau',
        'h_partners'   => 'Partenaires',
        'pitch_title'  => 'Associez votre marque à la longue distance.',
        'pitch_text'   => 'Un athlète professionnel ancré en Bretagne et présent sur le circuit international. Visibilité en course, contenus, tests de matériel, interventions en entreprise : construisons un partenariat à votre mesure.',
        'pitch_points' => ['Visibilité en course et sur les réseaux', 'Retours terrain et tests de matériel', 'Interventions et sorties avec vos équipes'],
        'pitch_cta'    => 'Parler d\'un partenariat',
        'h_calendar'   => 'Calendrier',
        'no_events'    => 'Le programme de la prochaine saison sera annoncé ici.',
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
        'draft'        => 'Site in preparation — content under review.',
        'on_request'   => 'On request',
        'h_highlights' => 'Highlights.',
        'see_results'  => 'See the results',
        'see_worlds'   => 'World Championships',
        'show_all'     => 'Show all results',
        'worlds_big'   => ['IRONMAN World Championship.', '%d times on the start line.'],
        'services_big' => ['Your preparation.', 'A pro\'s toolkit.'],
        'slides'       => [
            ['roth-arrivee', 'Challenge Roth 2026.', '7:46:30 over the IRONMAN distance.'],
            ['brasil-podium', 'IRONMAN Brasil 2026.', 'Third place in 7:38.'],
            ['roth-velo', '180 km on the bike.', 'Where the race is built.'],
            ['roth-course', 'Then a marathon.', 'All the way to the line.'],
        ],
        'alt_hero'     => 'Arnaud Guilloux riding between two walls of spectators at Challenge Roth',
        'alt_kona'     => ['Arnaud Guilloux and his bike on a lava field in Hawaii', 'Arnaud Guilloux at bike check-in for the IRONMAN World Championship'],
        'alt_portrait' => 'Arnaud Guilloux in front of the Petit Minou lighthouse, Brittany',
        'nav'          => ['palmares' => 'Results', 'mondiaux' => 'Worlds', 'prestations' => 'Services', 'partenaires' => 'Partners', 'calendrier' => 'Calendar', 'contact' => 'Contact'],
        'cta_coaching' => 'Coaching & testing',
        'cta_sponsor'  => 'Become a partner',
        'place'        => 'Saint-Renan, Brittany',
        'h_results'    => 'Results',
        'h_qualif'     => 'World Championship qualifications',
        'qualif_lead'  => 'In the pro field, a qualification is earned one race at a time.',
        'h_services'   => 'Services',
        'services_lead' => 'What I apply to my own preparation, put to work for yours. In Saint-Renan or remotely, depending on the service.',
        'from'         => 'From',
        'ask'          => 'Request a slot',
        'h_partners'   => 'Partners',
        'pitch_title'  => 'Put your brand on the long-distance stage.',
        'pitch_text'   => 'A professional athlete rooted in Brittany and racing the international circuit. Race-day visibility, content, product testing, talks for your teams: let\'s build a partnership that fits you.',
        'pitch_points' => ['Visibility on race day and on social media', 'Field feedback and product testing', 'Talks and rides with your teams'],
        'pitch_cta'    => 'Talk about a partnership',
        'h_calendar'   => 'Calendar',
        'no_events'    => 'Next season\'s schedule will be announced here.',
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

$socials = [];
foreach (['instagram' => 'Instagram', 'strava' => 'Strava', 'linkedin' => 'LinkedIn'] as $key => $label) {
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
<meta name="theme-color" content="#000000">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=2">
<script>document.documentElement.classList.add('js');</script>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<?php if ($draft): ?><p class="draft"><?= e($T['draft']) ?></p><?php endif; ?>

<header class="top">
  <div class="wrap">
    <a class="brand" href="<?= e($home[$lang]) ?>">Arnaud Guilloux</a>
    <nav class="nav" aria-label="Sections">
      <?php foreach ($T['nav'] as $id => $label): ?>
      <a href="#<?= $id ?>"><?= e($label) ?></a>
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
    <p class="mono kicker"><?= e(tr($hero, 'kicker')) ?></p>
    <h1>Arnaud Guilloux</h1>
    <p class="mono place">Nissa Triathlon · <?= e($T['place']) ?></p>
    <figure class="hero-media">
      <img src="<?= e($base) ?>assets/img/roth-solarer-berg.jpg" width="2000" height="1333" alt="<?= e($T['alt_hero']) ?>" fetchpriority="high">
    </figure>
    <div class="hero-cta reveal">
      <p class="lead"><?= e(tr($hero, 'lead')) ?></p>
      <div class="cta">
        <a class="btn" href="#prestations"><?= e($T['cta_coaching']) ?></a>
        <a class="btn ghost" href="#partenaires"><?= e($T['cta_sponsor']) ?></a>
      </div>
    </div>
    <?php if (!empty($c['stats'])): ?>
    <dl class="stats reveal">
      <?php foreach ($c['stats'] as $st): ?>
      <div>
        <dd><?= e($st['value'] ?? '') ?></dd>
        <dt><?= e(tr($st, 'label')) ?></dt>
      </div>
      <?php endforeach; ?>
    </dl>
    <?php endif; ?>
  </div>
</section>

<section class="zinc highlights">
  <div class="wrap head-row">
    <h2 class="reveal"><?= e($T['h_highlights']) ?></h2>
    <p class="links reveal">
      <a class="more" href="#palmares"><?= e($T['see_results']) ?></a>
      <a class="more" href="#mondiaux"><?= e($T['see_worlds']) ?></a>
    </p>
  </div>
  <div class="carousel" id="carousel" tabindex="0" aria-label="<?= e($T['h_highlights']) ?>">
    <?php foreach ($T['slides'] as $sl): ?>
    <figure class="slide">
      <img src="<?= e($base) ?>assets/img/<?= e($sl[0]) ?>.jpg" alt="" loading="lazy">
      <figcaption><strong><?= e($sl[1]) ?></strong><span><?= e($sl[2]) ?></span></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="dots" id="dots" aria-hidden="true">
    <?php foreach ($T['slides'] as $i => $sl): ?><span<?= $i === 0 ? ' class="on"' : '' ?>></span><?php endforeach; ?>
  </div>
</section>

<?php if (!empty($c['results'])):
    $firstRows = 8; ?>
<section id="palmares">
  <div class="wrap">
    <h2 class="reveal"><?= e($T['h_results']) ?>.</h2>
    <ol class="rows results reveal" id="results">
      <?php foreach (array_values($c['results']) as $i => $r): ?>
      <li<?= $i >= $firstRows ? ' class="extra"' : '' ?>>
        <span class="mono year"><?= e($r['year'] ?? '') ?></span>
        <span class="rank"><?= rank_label(trim((string) ($r['rank'] ?? ''))) ?></span>
        <span class="what">
          <strong><?= e($r['race'] ?? '') ?></strong>
          <?php if (($r['location'] ?? '') !== ''): ?><span class="where"><?= e($r['location']) ?></span><?php endif; ?>
        </span>
        <span class="mono note"><?= e(tr($r, 'note')) ?></span>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php if (count($c['results']) > $firstRows): ?>
    <button class="more" type="button" id="show-all" hidden><?= e($T['show_all']) ?> (<?= count($c['results']) ?>)</button>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($c['qualifications'])): ?>
<section id="mondiaux" class="zinc">
  <div class="wrap">
    <p class="eyebrow reveal"><?= e($T['h_qualif']) ?></p>
    <h2 class="big reveal"><?= e($T['worlds_big'][0]) ?><br><span><?= e(sprintf($T['worlds_big'][1], count($c['qualifications']))) ?></span></h2>
    <div class="duo">
      <figure><img class="grow" src="<?= e($base) ?>assets/img/kona-lave.jpg" alt="<?= e($T['alt_kona'][0]) ?>" loading="lazy"></figure>
      <figure><img class="grow" src="<?= e($base) ?>assets/img/kona-check-in.jpg" alt="<?= e($T['alt_kona'][1]) ?>" loading="lazy"></figure>
    </div>
    <p class="feature-text reveal"><?= e($T['qualif_lead']) ?></p>
    <ul class="qualifs reveal">
      <?php foreach ($c['qualifications'] as $q): ?>
      <li>
        <span class="q-year"><?= e($q['year'] ?? '') ?></span>
        <span class="q-what">
          <strong><?= e($q['event'] ?? '') ?></strong>
          <span><?= e($q['location'] ?? '') ?></span>
        </span>
        <span class="mono note"><?= e(tr($q, 'note')) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($c['services'])): ?>
<section id="prestations">
  <div class="wrap">
    <div class="split">
      <div>
        <p class="eyebrow reveal"><?= e($T['h_services']) ?></p>
        <h2 class="big reveal"><?= e($T['services_big'][0]) ?><br><span><?= e($T['services_big'][1]) ?></span></h2>
        <p class="feature-text reveal"><?= e($T['services_lead']) ?></p>
      </div>
      <figure class="portrait"><img class="grow" src="<?= e($base) ?>assets/img/petit-minou.jpg" alt="<?= e($T['alt_portrait']) ?>" loading="lazy"></figure>
    </div>
    <div class="services">
      <?php foreach ($c['services'] as $sv):
          $price = trim((string) ($sv['price'] ?? '')); ?>
      <article class="card reveal">
        <h3><?= e(tr($sv, 'title')) ?></h3>
        <p><?= e(tr($sv, 'desc')) ?></p>
        <div class="price">
          <?php if ($price !== ''): ?>
          <span class="mono"><?= e($T['from']) ?></span>
          <strong><?= e($price) ?> €</strong>
          <?php else: ?>
          <strong><?= e($T['on_request']) ?></strong>
          <?php endif; ?>
          <span class="unit"><?= e(tr($sv, 'unit')) ?></span>
        </div>
        <a class="more" href="#contact"><?= e($T['ask']) ?></a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="partenaires" class="zinc">
  <div class="wrap">
    <p class="eyebrow reveal"><?= e($T['h_partners']) ?></p>
    <h2 class="big reveal"><?= e($T['pitch_title']) ?></h2>
    <div class="pitch">
      <div class="reveal">
        <p class="feature-text"><?= e($T['pitch_text']) ?></p>
        <ul>
          <?php foreach ($T['pitch_points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?>
        </ul>
        <a class="btn" href="#contact"><?= e($T['pitch_cta']) ?></a>
      </div>
      <?php if (!empty($c['partners'])): ?>
      <ul class="partners reveal">
        <?php foreach ($c['partners'] as $p):
            $purl = trim((string) ($p['url'] ?? ''));
            $ok   = (bool) preg_match('#^https?://#i', $purl); ?>
        <li>
          <?php if ($ok): ?><a href="<?= e($purl) ?>" rel="noopener" target="_blank"><?php endif; ?>
          <strong><?= e($p['name'] ?? '') ?></strong>
          <span class="mono"><?= e(tr($p, 'role')) ?></span>
          <?php if ($ok): ?></a><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="calendrier">
  <div class="wrap">
    <h2 class="reveal"><?= e($T['h_calendar']) ?>.</h2>
    <?php if ($upcoming): ?>
    <ol class="rows calendar reveal">
      <?php foreach ($upcoming as $ev):
          [$y, $m, $d] = array_map('intval', explode('-', $ev['date'])); ?>
      <li>
        <time datetime="<?= e($ev['date']) ?>"><b><?= sprintf('%02d', $d) ?></b><span class="mono"><?= e($months[$lang][$m - 1] ?? '') ?> <?= $y ?></span></time>
        <span class="what">
          <strong><?= e($ev['race'] ?? '') ?></strong>
          <?php if (($ev['location'] ?? '') !== ''): ?><span class="where"><?= e($ev['location']) ?></span><?php endif; ?>
        </span>
        <span class="mono note"><?= e(tr($ev, 'note')) ?></span>
        <?php if (($ev['format'] ?? '') !== ''): ?><span class="mono tag"><?= e($ev['format']) ?></span><?php else: ?><span></span><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <p class="feature-text reveal" style="margin-top:28px"><?= e($T['no_events']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section id="contact" class="zinc">
  <div class="wrap contact">
    <div class="reveal">
      <h2><?= e($T['h_contact']) ?>.</h2>
      <p class="feature-text"><?= e($T['contact_lead']) ?></p>
      <?php if ($email !== ''): ?><p><a class="mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
      <?php if ($socials): ?>
      <ul class="socials">
        <?php foreach ($socials as $label => $url): ?><li><a class="more" href="<?= e($url) ?>" rel="noopener me" target="_blank"><?= e($label) ?></a></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <form class="card reveal" method="post" action="<?= e($base) ?>contact.php">
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

<script>
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var items = document.querySelectorAll('.reveal, .grow');
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -12% 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  var car = document.getElementById('carousel'), dots = document.getElementById('dots');
  if (car && dots && 'IntersectionObserver' in window) {
    var slides = car.querySelectorAll('.slide'), marks = dots.children;
    Array.prototype.forEach.call(marks, function (m, i) {
      m.addEventListener('click', function () { slides[i].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', inline: 'center', block: 'nearest' }); });
    });
    var so = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          var k = Array.prototype.indexOf.call(slides, en.target);
          Array.prototype.forEach.call(marks, function (m, i) { m.classList.toggle('on', i === k); });
        }
      });
    }, { root: car, threshold: 0.6 });
    slides.forEach(function (s) { so.observe(s); });
  }

  var btn = document.getElementById('show-all'), list = document.getElementById('results');
  if (btn && list) {
    list.classList.add('collapsed'); btn.hidden = false;
    btn.addEventListener('click', function () { list.classList.remove('collapsed'); btn.hidden = true; });
  }
})();
</script>
</body>
</html>
