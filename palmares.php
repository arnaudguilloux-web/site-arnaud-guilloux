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
             'f_label' => 'Filtrer le palmarès', 'f_year' => 'Année', 'f_kind' => 'Type', 'f_cont' => 'Continent', 'f_all' => 'Tous', 'f_all_y' => 'Toutes', 'k_wc' => 'Mondiaux', 'none' => 'Aucune course ne correspond à ces filtres.', 'reset' => 'Tout afficher', 'one' => 'course', 'many' => 'courses',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Results — Arnaud Guilloux', 'h1' => 'Results', 'back' => 'Back to home', 'worlds' => 'IRONMAN World Championship', 'badge' => 'WC',
             'f_label' => 'Filter the results', 'f_year' => 'Year', 'f_kind' => 'Type', 'f_cont' => 'Continent', 'f_all' => 'All', 'f_all_y' => 'All', 'k_wc' => 'Worlds', 'none' => 'No race matches these filters.', 'reset' => 'Show all', 'one' => 'race', 'many' => 'races',
             'legal' => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.'],
][$lang];

/* Filtres (liens, sans JavaScript) : ?year=2024&kind=im70&continent=eu — cumulables, conservés d'un clic à l'autre. */
$all   = results_rows($c);
$kinds = ['wc' => $T['k_wc'], 'im' => 'IRONMAN', 'im70' => 'IRONMAN 70.3', 'ch' => 'Challenge'];
$kindOf = static function (array $r): array {
    $keys  = [];
    if (!empty($r['worlds'])) { $keys[] = 'wc'; }
    $keys[] = ['IRONMAN' => 'im', 'IRONMAN 70.3' => 'im70', 'Challenge' => 'ch'][race_label($r)] ?? '';
    return array_values(array_filter($keys, 'strlen'));
};
$conts = [];
foreach (continent_names() as $code => $nm) { $conts[$code] = $nm[$lang === 'en' ? 1 : 0]; }
$fy = (string) ($_GET['year'] ?? '');
$fk = (string) ($_GET['kind'] ?? '');
$fc = (string) ($_GET['continent'] ?? '');
/* Options réellement présentes dans les données */
$years   = [];
$kindsIn = [];
$contsIn = [];
foreach ($all as $r) {
    if (($r['year'] ?? '') !== '') { $years[$r['year']] = true; }
    foreach ($kindOf($r) as $kk) { $kindsIn[$kk] = true; }
    $cc = race_continent($r);
    if ($cc !== '') { $contsIn[$cc] = true; }
}
krsort($years);
$years   = array_map('strval', array_keys($years));
$kindsIn = array_intersect_key($kinds, $kindsIn);
$contsIn = array_intersect_key($conts, $contsIn);
if (!in_array($fy, $years, true))       { $fy = ''; }
if (!isset($kindsIn[$fk]))              { $fk = ''; }
if (!isset($contsIn[$fc]))              { $fc = ''; }
$rows = array_values(array_filter($all, static function (array $r) use ($fy, $fk, $fc, $kindOf): bool {
    return ($fy === '' || ($r['year'] ?? '') === $fy)
        && ($fk === '' || in_array($fk, $kindOf($r), true))
        && ($fc === '' || race_continent($r) === $fc);
}));
$href = static function (array $over) use ($fy, $fk, $fc, $lang): string {
    $q = array_filter(array_merge(['year' => $fy, 'kind' => $fk, 'continent' => $fc], $over), 'strlen');
    if ($lang === 'en') { $q['lang'] = 'en'; }
    return 'palmares.php' . ($q ? '?' . http_build_query($q) : '');
};
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=94">
<link rel="icon" href="<?= e($base) ?>assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="<?= e($base) ?>favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e($base) ?>assets/apple-touch-icon.png">
</head>
<body>
<?= site_header($lang, $home, $self, $c, 'palmares') ?>

<main>
<section class="gal-page">
  <div class="wrap">
    <a class="back" href="<?= e($home[$lang]) ?>"><?= e($T['back']) ?></a>
    <h1><?= e($T['h1']) ?></h1>
    <div class="filter-groups" role="group" aria-label="<?= e($T['f_label']) ?>">
      <?php foreach ([['year', $T['f_year'], $T['f_all_y'], array_combine($years, $years), $fy], ['kind', $T['f_kind'], $T['f_all'], $kindsIn, $fk], ['continent', $T['f_cont'], $T['f_all'], $contsIn, $fc]] as [$param, $title, $allLabel, $opts, $cur]):
          if (count($opts) < 2) { continue; } ?>
      <div class="filter-group">
        <span class="mono"><?= e($title) ?></span>
        <nav class="filters" aria-label="<?= e($title) ?>">
          <a href="<?= e($base . $href([$param => ''])) ?>"<?= $cur === '' ? ' aria-current="page"' : '' ?>><?= e($allLabel) ?></a>
          <?php foreach ($opts as $val => $label): ?>
          <a href="<?= e($base . $href([$param => (string) $val])) ?>"<?= (string) $val === $cur ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="mono results-count" aria-live="polite"><?= count($rows) ?> <?= e(count($rows) > 1 ? $T['many'] : $T['one']) ?></p>
    <?php if ($rows): ?>
    <ol class="rows results all-results">
      <?php foreach ($rows as $r): ?>
      <?= result_li($r, $T['worlds'], $T['badge']) . "\n" ?>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <p class="no-results"><?= e($T['none']) ?> <a href="<?= e($base . $href(['year' => '', 'kind' => '', 'continent' => ''])) ?>"><?= e($T['reset']) ?></a></p>
    <?php endif; ?>
  </div>
</section>
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>
</body>
</html>
