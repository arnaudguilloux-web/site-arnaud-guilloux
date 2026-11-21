<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';

$lang = (($_GET['lang'] ?? '') === 'en') ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/coaching.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$self = ['fr' => $base . 'coaching.php', 'en' => $base . 'coaching.php?lang=en'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;
$c = load_content();
$settings = $c['settings'] ?? [];
$coaching = $c['coaching'] ?? [];
$coachItems = coaching_items($c);
$quotes = testimonials($c);
$draft = ($settings['draft'] ?? '1') === '1';

$T = [
    'fr' => ['title' => 'Coaching — Arnaud Guilloux', 'kicker' => 'Arnaud Guilloux · Triathlète professionnel', 'h1' => 'Le Coaching', 'place' => 'Orienté performance',
             'alt' => 'Arnaud Guilloux en course à pied sur le Challenge Roth', 'cta' => 'Parler de votre objectif',
             'h_offers' => 'Offres', 'offers_big' => ['Ce que', 'je propose.'], 'card_cta' => 'Me contacter',
             'h_quotes' => 'Témoignages', 'quotes_big' => ['Ce que disent', 'mes athlètes.'], 'quotes_note' => '', 'prev' => 'Précédent', 'next' => 'Suivant',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Coaching — Arnaud Guilloux', 'kicker' => 'Arnaud Guilloux · Professional triathlete', 'h1' => 'Coaching', 'place' => 'Performance-driven',
             'alt' => 'Arnaud Guilloux running at Challenge Roth', 'cta' => 'Talk about your goal',
             'h_offers' => 'Offers', 'offers_big' => ['What', 'I offer.'], 'card_cta' => 'Get in touch',
             'h_quotes' => 'Testimonials', 'quotes_big' => ['What my', 'athletes say.'], 'quotes_note' => 'Testimonials are shown in their original French.', 'prev' => 'Previous', 'next' => 'Next',
             'legal' => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.'],
][$lang];
$F = contact_strings($lang);
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($T['title']) ?></title>
<?php if ($draft): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="canonical" href="<?= e($origin . $self[$lang]) ?>">
<link rel="alternate" hreflang="fr" href="<?= e($origin . $self['fr']) ?>">
<link rel="alternate" hreflang="en" href="<?= e($origin . $self['en']) ?>">
<meta name="theme-color" content="#ffffff">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=66">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'coaching') ?>

<main>
<section class="hero">
  <div class="wrap">
    <div class="hero-panel">
      <img class="hero-bg" src="<?= e($base) ?>assets/img/roth-course.jpg" width="1620" height="1080" alt="<?= e($T['alt']) ?>" fetchpriority="high">
      <div class="hero-inner">
        <div class="hero-copy">
          <p class="mono kicker"><?= e($T['kicker']) ?></p>
          <h1><?= e($T['h1']) ?></h1>
          <p class="mono place"><?= e($T['place']) ?></p>
        </div>
        <div class="cta">
          <a class="btn" href="#contact" data-contact="coaching"><?= e($T['cta']) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (tr($coaching, 'intro') !== ''): ?>
<div class="coach-intro">
  <div class="wrap"><p class="coach-lead"><?= e(tr($coaching, 'intro')) ?></p></div>
</div>
<?php endif; ?>

<?php if ($coachItems): ?>
<section id="offres">
  <div class="wrap">
    <p class="eyebrow"><?= e($T['h_offers']) ?></p>
    <h2 class="big"><?= e($T['offers_big'][0]) ?> <span><?= e($T['offers_big'][1]) ?></span></h2>
    <ul class="offer-cards">
      <?php foreach ($coachItems as $it): ?>
      <li class="offer-card">
        <h3><?= e(tr($it, 'title')) ?></h3>
        <?php if (tr($it, 'detail') !== ''): ?><p class="offer-detail"><?= e(tr($it, 'detail')) ?></p><?php endif; ?>
        <?php if (tr($it, 'price') !== ''): ?><p class="offer-price"><?= e(tr($it, 'price')) ?></p><?php endif; ?>
        <a class="card-cta" href="#contact" data-contact="coaching"><?= e($T['card_cta']) ?></a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if ($quotes): ?>
<section id="temoignages">
  <div class="wrap">
    <p class="eyebrow"><?= e($T['h_quotes']) ?></p>
    <h2 class="big"><?= e($T['quotes_big'][0]) ?> <span><?= e($T['quotes_big'][1]) ?></span></h2>
  </div>
  <div class="carousel qcar" id="qcar" tabindex="0" aria-label="<?= e($T['h_quotes']) ?>">
    <?php foreach ($quotes as $q):
        $qname = trim((string) ($q['name'] ?? ''));
        $qcity = trim((string) ($q['city'] ?? ''));
        $qhl   = tr($q, 'highlight');
        $qctx  = tr($q, 'context'); ?>
    <figure class="qcard">
      <?php if ($qhl !== ''): ?>
      <div class="q-hl"><strong><?= e($qhl) ?></strong><?php if ($qctx !== ''): ?><span class="mono"><?= e($qctx) ?></span><?php endif; ?></div>
      <?php endif; ?>
      <blockquote lang="fr"><p>«&nbsp;<?= e(trim((string) $q['quote'])) ?>&nbsp;»</p></blockquote>
      <?php if ($qname !== ''): ?><figcaption class="mono"><?= e($qname) ?><?= $qcity !== '' ? ' – ' . e($qcity) : '' ?></figcaption><?php endif; ?>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="wrap">
    <div class="qctrl">
      <button type="button" class="arrow" id="qprev" aria-label="<?= e($T['prev']) ?>">‹</button>
      <div class="dots" id="qdots" aria-hidden="true">
        <?php foreach ($quotes as $i => $q): ?><span<?= $i === 0 ? ' class="on"' : '' ?>></span><?php endforeach; ?>
      </div>
      <button type="button" class="arrow" id="qnext" aria-label="<?= e($T['next']) ?>">›</button>
    </div>
    <?php if ($T['quotes_note'] !== ''): ?><p class="mono quotes-note"><?= e($T['quotes_note']) ?></p><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?= contact_section_html($F, $lang, $base, null, social_links($settings), 'coaching') ?>
</main>

<?= contact_dialog_html($F, $lang, $base) ?>
<?= site_footer($T['legal'], $settings, $lang) ?>
<script src="<?= e($base) ?>assets/carousel.js?v=1"></script>
<script>
carousel('qcar', 'qdots', 'qprev', 'qnext', '.qcard');
</script>
</body>
</html>
