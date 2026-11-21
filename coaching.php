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
$draft = ($settings['draft'] ?? '1') === '1';

$T = [
    'fr' => ['title' => 'Coaching — Arnaud Guilloux', 'back' => 'Retour à l\'accueil', 'h_coaching' => 'Coaching', 'big' => ['Le coaching.', 'Orienté performance.'], 'cta' => 'Parler de votre objectif',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Coaching — Arnaud Guilloux', 'back' => 'Back to home', 'h_coaching' => 'Coaching', 'big' => ['Coaching.', 'Performance-driven.'], 'cta' => 'Talk about your goal',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=44">
</head>
<body>
<header class="top">
  <div class="wrap">
    <a class="brand" href="<?= e($home[$lang]) ?>">Arnaud <strong>Guilloux</strong></a>
    <div class="lang" aria-label="Langue / Language">
      <a href="<?= e($self['fr']) ?>" hreflang="fr" lang="fr"<?= $lang === 'fr' ? ' aria-current="true"' : '' ?>>FR</a>
      <a href="<?= e($self['en']) ?>" hreflang="en" lang="en"<?= $lang === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
    </div>
  </div>
</header>

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
</main>

<footer class="foot">
  <div class="wrap">
    <p><?= e($T['legal']) ?></p>
    <p>© <?= date('Y') ?> Arnaud Guilloux</p>
  </div>
</footer>
</body>
</html>
