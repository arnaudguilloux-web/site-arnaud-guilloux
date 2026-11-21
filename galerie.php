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

$T = [
    'fr' => ['title' => 'Galerie — Arnaud Guilloux', 'h1' => 'Galerie', 'back' => 'Retour à l\'accueil',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Gallery — Arnaud Guilloux', 'h1' => 'Gallery', 'back' => 'Back to home',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=57">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'highlights') ?>

<main>
<section class="gal-page">
  <div class="wrap">
    <a class="back" href="<?= e($home[$lang]) ?>"><?= e($T['back']) ?></a>
    <h1><?= e($T['h1']) ?></h1>
    <div class="gallery">
      <?php foreach (gallery_items() as $g): ?>
      <figure>
        <img src="<?= e($base) ?>assets/img/<?= e($g[0]) ?>.jpg" alt="<?= e($g[$lang === 'en' ? 3 : 1] . ' ' . $g[$lang === 'en' ? 4 : 2]) ?>" loading="lazy">
        <figcaption><strong><?= e($g[$lang === 'en' ? 3 : 1]) ?></strong><span><?= e($g[$lang === 'en' ? 4 : 2]) ?></span></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>

<?= site_footer($T['legal'], $settings) ?>
</body>
</html>
