<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';

$lang = (($_GET['lang'] ?? '') === 'en') ? 'en' : 'fr';
$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/palmares.php')));
$home = ['fr' => $base, 'en' => $base . 'en/'];
$self = ['fr' => $base . 'palmares.php', 'en' => $base . 'palmares.php?lang=en'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;
$c = load_content();
$settings = $c['settings'] ?? [];
$draft = ($settings['draft'] ?? '1') === '1';

$T = [
    'fr' => ['title' => 'Palmarès — Arnaud Guilloux', 'h1' => 'Palmarès', 'back' => 'Retour à l\'accueil', 'worlds' => 'Championnat du monde IRONMAN', 'badge' => 'WC',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Results — Arnaud Guilloux', 'h1' => 'Results', 'back' => 'Back to home', 'worlds' => 'IRONMAN World Championship', 'badge' => 'WC',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=70">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'palmares') ?>

<main>
<section class="gal-page">
  <div class="wrap">
    <a class="back" href="<?= e($home[$lang]) ?>"><?= e($T['back']) ?></a>
    <h1><?= e($T['h1']) ?></h1>
    <ol class="rows results all-results">
      <?php foreach (results_rows($c) as $r): ?>
      <?= result_li($r, $T['worlds'], $T['badge']) . "\n" ?>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>
</body>
</html>
