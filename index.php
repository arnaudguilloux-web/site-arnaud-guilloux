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
        'draft'        => 'Contenu d\'exemple — à remplacer depuis le back office avant mise en ligne.',
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
        'draft'        => 'Sample content — replace it from the back office before going live.',
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
<meta name="theme-color" content="#0a0a0a">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body>
<?php if ($draft): ?><p class="draft"><?= e($T['draft']) ?></p><?php endif; ?>

<header class="top">
  <a class="brand" href="<?= e($home[$lang]) ?>">Arnaud Guilloux</a>
  <nav class="nav" aria-label="Sections">
    <?php foreach ($T['nav'] as $id => $label): ?>
    <a href="#<?= $id ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="lang" aria-label="Langue / Language">
    <a href="<?= e($home['fr']) ?>" hreflang="fr" lang="fr"<?= $lang === 'fr' ? ' aria-current="true"' : '' ?>>FR</a>
    <span aria-hidden="true">/</span>
    <a href="<?= e($home['en']) ?>" hreflang="en" lang="en"<?= $lang === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
  </div>
</header>

<main>
<section class="hero dark">
  <div class="wrap">
    <div class="hero-meta mono">
      <span><?= e(tr($hero, 'kicker')) ?></span>
      <span>Nissa Triathlon — <?= e($T['place']) ?> · 48.43° N, 4.62° W</span>
    </div>
    <h1>Arnaud<br>Guilloux</h1>
    <div class="hero-foot">
      <p class="lead"><?= e(tr($hero, 'lead')) ?></p>
      <div class="cta">
        <a class="btn solid" href="#prestations"><?= e($T['cta_coaching']) ?></a>
        <a class="btn" href="#partenaires"><?= e($T['cta_sponsor']) ?></a>
      </div>
    </div>
    <?php if (!empty($c['stats'])): ?>
    <dl class="stats">
      <?php foreach ($c['stats'] as $st): ?>
      <div>
        <dd><?= e($st['value'] ?? '') ?></dd>
        <dt class="mono"><?= e(tr($st, 'label')) ?></dt>
      </div>
      <?php endforeach; ?>
    </dl>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($c['results'])): ?>
<section id="palmares">
  <div class="wrap">
    <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_results']) ?></h2></header>
    <ol class="rows results">
      <?php foreach ($c['results'] as $r): ?>
      <li>
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
  </div>
</section>
<?php endif; ?>

<?php if (!empty($c['qualifications'])): ?>
<section id="mondiaux" class="dark">
  <div class="wrap">
    <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_qualif']) ?></h2></header>
    <p class="section-lead"><?= e($T['qualif_lead']) ?></p>
    <ul class="qualifs">
      <?php foreach ($c['qualifications'] as $q): ?>
      <li>
        <span class="q-year"><?= e($q['year'] ?? '') ?></span>
        <span class="q-what">
          <strong><?= e($q['event'] ?? '') ?></strong>
          <span class="mono"><?= e($q['location'] ?? '') ?><?php $note = tr($q, 'note'); if ($note !== ''): ?> · <?= e($note) ?><?php endif; ?></span>
        </span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($c['services'])): ?>
<section id="prestations">
  <div class="wrap">
    <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_services']) ?></h2></header>
    <p class="section-lead"><?= e($T['services_lead']) ?></p>
    <div class="services">
      <?php foreach ($c['services'] as $i => $sv): ?>
      <article class="service">
        <span class="mono"><?= chr(65 + ($i % 26)) ?></span>
        <h3><?= e(tr($sv, 'title')) ?></h3>
        <p><?= e(tr($sv, 'desc')) ?></p>
        <div class="price">
          <span class="mono"><?= e($T['from']) ?></span>
          <strong><?= e($sv['price'] ?? '') ?> €</strong>
          <span class="mono"><?= e(tr($sv, 'unit')) ?></span>
        </div>
        <a class="link" href="#contact"><?= e($T['ask']) ?> →</a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="partenaires" class="tint">
  <div class="wrap">
    <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_partners']) ?></h2></header>
    <?php if (!empty($c['partners'])): ?>
    <ul class="partners">
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
    <div class="pitch">
      <h3><?= e($T['pitch_title']) ?></h3>
      <div>
        <p><?= e($T['pitch_text']) ?></p>
        <ul class="mono">
          <?php foreach ($T['pitch_points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?>
        </ul>
        <a class="btn solid" href="#contact"><?= e($T['pitch_cta']) ?></a>
      </div>
    </div>
  </div>
</section>

<section id="calendrier">
  <div class="wrap">
    <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_calendar']) ?></h2></header>
    <?php if ($upcoming): ?>
    <ol class="rows calendar">
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
    <p class="section-lead"><?= e($T['no_events']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section id="contact" class="dark">
  <div class="wrap contact">
    <div>
      <header class="head"><span class="mono"><?= sprintf('%02d', ++$n) ?></span><h2><?= e($T['h_contact']) ?></h2></header>
      <p class="section-lead"><?= e($T['contact_lead']) ?></p>
      <?php if ($email !== ''): ?><p><a class="mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
      <?php if ($socials): ?>
      <ul class="socials mono">
        <?php foreach ($socials as $label => $url): ?><li><a href="<?= e($url) ?>" rel="noopener me" target="_blank"><?= e($label) ?> ↗</a></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <form method="post" action="<?= e($base) ?>contact.php">
      <?php if ($sent === '1'): ?><p class="flash" role="status"><?= e($T['sent_ok']) ?></p>
      <?php elseif ($sent === '0'): ?><p class="flash" role="alert"><?= e($T['sent_ko']) ?></p><?php endif; ?>
      <input type="hidden" name="lang" value="<?= $lang ?>">
      <input type="hidden" name="ts" value="<?= time() ?>">
      <p class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
      <label><span class="mono"><?= e($T['f_name']) ?></span><input type="text" name="name" required maxlength="120" autocomplete="name"></label>
      <label><span class="mono"><?= e($T['f_email']) ?></span><input type="email" name="email" required maxlength="160" autocomplete="email"></label>
      <label><span class="mono"><?= e($T['f_topic']) ?></span>
        <select name="topic">
          <?php foreach ($T['topics'] as $val => $label): ?><option value="<?= $val ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label><span class="mono"><?= e($T['f_message']) ?></span><textarea name="message" rows="5" required maxlength="5000"></textarea></label>
      <button class="btn solid" type="submit"><?= e($T['f_send']) ?></button>
    </form>
  </div>
</section>
</main>

<footer class="foot dark">
  <div class="wrap mono">
    <span>© <?= date('Y') ?> Arnaud Guilloux</span>
    <span><?= e($T['legal']) ?><?php if (trim((string) ($settings['siret'] ?? '')) !== ''): ?> SIRET <?= e($settings['siret']) ?>.<?php endif; ?></span>
  </div>
</footer>
</body>
</html>
