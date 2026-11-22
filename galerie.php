<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';
require_once __DIR__ . '/lib_gallery.php';

$lang = (($_GET['lang'] ?? '') === 'en') ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/galerie.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$self = ['fr' => $base . 'galerie.php', 'en' => $base . 'galerie.php?lang=en'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;
$c = load_content();
$settings = ($c['settings'] ?? []);
$draft = ($settings['draft'] ?? '1') === '1';

$type = in_array($_GET['type'] ?? '', ['athlete', 'coach'], true) ? (string) $_GET['type'] : '';
$items = gallery_items($type !== '' ? $type : null);
$hasCoach = count(gallery_items('coach')) > 0;
$hasAthlete = count(gallery_items('athlete')) > 0;

$T = [
    'fr' => ['title' => 'Galerie — Arnaud Guilloux', 'h1' => 'Galerie', 'back' => 'Retour à l\'accueil', 'f_all' => 'Tout', 'f_athlete' => 'Athlète', 'f_coach' => 'Coach', 'f_label' => 'Filtrer par type', 'lb_zoom' => 'Agrandir', 'lb_close' => 'Fermer', 'lb_prev' => 'Photo précédente', 'lb_next' => 'Photo suivante',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Gallery — Arnaud Guilloux', 'h1' => 'Gallery', 'back' => 'Back to home', 'f_all' => 'All', 'f_athlete' => 'Athlete', 'f_coach' => 'Coach', 'f_label' => 'Filter by type', 'lb_zoom' => 'Enlarge', 'lb_close' => 'Close', 'lb_prev' => 'Previous photo', 'lb_next' => 'Next photo',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=79">
<link rel="icon" href="<?= e($base) ?>assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="<?= e($base) ?>favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e($base) ?>assets/apple-touch-icon.png">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'highlights') ?>

<main>
<section class="gal-page">
  <div class="wrap">
    <a class="back" href="<?= e($home[$lang]) ?>"><?= e($T['back']) ?></a>
    <h1><?= e($T['h1']) ?></h1>
    <?php if ($hasCoach && $hasAthlete):
        $q = static function (string $t) use ($lang): string { return 'galerie.php' . ($t !== '' || $lang === 'en' ? '?' . http_build_query(array_filter(['type' => $t, 'lang' => $lang === 'en' ? 'en' : ''])) : ''); }; ?>
    <nav class="filters" aria-label="<?= e($T['f_label']) ?>">
      <?php foreach (['' => 'f_all', 'athlete' => 'f_athlete', 'coach' => 'f_coach'] as $t => $k): ?>
      <a href="<?= e($base . $q($t)) ?>"<?= $t === $type ? ' aria-current="page"' : '' ?>><?= e($T[$k]) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <div class="gallery">
      <?php foreach ($items as $g): ?>
      <figure>
        <button type="button" class="g-zoom" aria-label="<?= e($T['lb_zoom'] . ' : ' . $g[$lang === 'en' ? 3 : 1] . ' ' . $g[$lang === 'en' ? 4 : 2]) ?>">
          <img src="<?= e($base) ?>assets/img/<?= e($g[0]) ?>.jpg" alt="<?= e($g[$lang === 'en' ? 3 : 1] . ' ' . $g[$lang === 'en' ? 4 : 2]) ?>" loading="lazy">
        </button>
        <figcaption><strong><?= e($g[$lang === 'en' ? 3 : 1]) ?></strong><span><?= e($g[$lang === 'en' ? 4 : 2]) ?></span></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>

<dialog class="lightbox" id="lightbox" aria-label="<?= e($T['h1']) ?>">
  <button type="button" class="lb-close" aria-label="<?= e($T['lb_close']) ?>">✕</button>
  <button type="button" class="lb-nav lb-prev" aria-label="<?= e($T['lb_prev']) ?>">‹</button>
  <figure class="lb-stage">
    <img alt="">
    <figcaption></figcaption>
  </figure>
  <button type="button" class="lb-nav lb-next" aria-label="<?= e($T['lb_next']) ?>">›</button>
</dialog>

<?= site_footer($T['legal'], $settings, $lang) ?>
<script src="<?= e($base) ?>assets/lightbox.js?v=1"></script>
</body>
</html>
