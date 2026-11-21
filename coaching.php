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
    'fr' => ['title' => 'Coaching — Arnaud Guilloux', 'back' => 'Retour à l\'accueil', 'h_coaching' => 'Coaching', 'big' => ['Le coaching.', 'Orienté performance.'], 'cta' => 'Parler de votre objectif', 'prev' => 'Précédent', 'next' => 'Suivant', 'h_quotes' => 'Témoignages', 'quotes_big' => ['Ce que disent', 'mes athlètes.'], 'quotes_note' => '',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Coaching — Arnaud Guilloux', 'back' => 'Back to home', 'h_coaching' => 'Coaching', 'big' => ['Coaching.', 'Performance-driven.'], 'cta' => 'Talk about your goal', 'prev' => 'Previous', 'next' => 'Next', 'h_quotes' => 'Testimonials', 'quotes_big' => ['What my', 'athletes say.'], 'quotes_note' => 'Testimonials are shown in their original French.',
             'legal' => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.'],
][$lang];
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=60">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'coaching') ?>

<main>
<section id="coaching">
  <div class="wrap">
    <a class="back" href="<?= e($home[$lang]) ?>"><?= e($T['back']) ?></a>
    <p class="eyebrow"><?= e($T['h_coaching']) ?></p>
    <h2 class="big"><?= e($T['big'][0]) ?> <span><?= e($T['big'][1]) ?></span></h2>
    <div class="coach">
      <div>
        <?php if (tr($coaching, 'intro') !== ''): ?><p class="feature-text"><?= e(tr($coaching, 'intro')) ?></p><?php endif; ?>
        <a class="btn" href="<?= e($home[$lang]) ?>#contact"><?= e($T['cta']) ?></a>
      </div>
      <?php if ($coachItems): ?>
      <ul class="offers">
        <?php foreach ($coachItems as $it): ?>
        <li>
          <span class="offer-what"><strong><?= e(tr($it, 'title')) ?></strong><?php if (tr($it, 'detail') !== ''): ?><span><?= e(tr($it, 'detail')) ?></span><?php endif; ?></span>
          <?php if (tr($it, 'price') !== ''): ?><span class="offer-price"><?= e(tr($it, 'price')) ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php if ($quotes): ?>
<section id="temoignages" class="soft">
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
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>
<script src="<?= e($base) ?>assets/carousel.js?v=1"></script>
<script>
carousel('qcar', 'qdots', 'qprev', 'qnext', '.qcard');
</script>
</body>
</html>
