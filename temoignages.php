<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';

$lang = (($_GET['lang'] ?? '') === 'en') ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/temoignages.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$self = ['fr' => $base . 'temoignages.php', 'en' => $base . 'temoignages.php?lang=en'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;
$c = load_content();
$settings = ($c['settings'] ?? []);
$draft = ($settings['draft'] ?? '1') === '1';

$quotes = testimonials($c);
$coachUrl = $base . 'coaching.php' . ($lang === 'en' ? '?lang=en' : '');

$T = [
    'fr' => ['title' => 'Témoignages — Arnaud Guilloux', 'h1' => 'Témoignages', 'back' => 'Retour au Coaching', 'note' => '',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Testimonials — Arnaud Guilloux', 'h1' => 'Testimonials', 'back' => 'Back to Coaching', 'note' => 'Testimonials are shown in their original French.',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=76">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'temoignages') ?>

<main>
<section class="gal-page">
  <div class="wrap">
    <a class="back" href="<?= e($coachUrl) ?>#temoignages"><?= e($T['back']) ?></a>
    <h1><?= e($T['h1']) ?></h1>
    <?php if ($T['note'] !== ''): ?><p class="mono quotes-note"><?= e($T['note']) ?></p><?php endif; ?>
    <div class="q-grid">
      <?php foreach ($quotes as $q): ?>
      <?= testimonial_card_html($q, $lang) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>
</body>
</html>
