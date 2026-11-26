<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_view.php';
require_once __DIR__ . '/lib_gallery.php';

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
$allQuotes = testimonials($c);
$moreQuotes = count($allQuotes) > 5;
/* Carrousel : 5 témoignages au hasard à chaque visite, tirés d'abord parmi ceux qui ont un chiffre clé ET un titre (contexte) ;
   s'il y en a moins de 5, on complète avec les autres. La page dédiée les montre tous. */
$featured = array_values(array_filter($allQuotes, static function ($q) { return tr($q, 'highlight') !== '' && tr($q, 'context') !== ''; }));
$others   = array_values(array_filter($allQuotes, static function ($q) { return tr($q, 'highlight') === '' || tr($q, 'context') === ''; }));
shuffle($featured);
shuffle($others);
$quotes = array_slice(array_merge($featured, $others), 0, 5);
$coachSlides = array_slice(gallery_items($c, 'coach'), 0, 5);   /* Highlights du Coach : 5 premières photos « coach » */
$draft = ($settings['draft'] ?? '1') === '1';

$T = [
    'fr' => ['title' => 'Coaching — Arnaud Guilloux', 'kicker' => 'Arnaud Guilloux · Triathlète professionnel', 'h1' => 'Le Coaching', 'place' => 'Orienté performance',
             'alt' => 'Arnaud Guilloux en course à pied sur le Challenge Roth', 'cta' => 'Parler de votre objectif',
             'h_offers' => 'Offres', 'offers_big' => ['Ce que', 'je propose.'], 'card_cta' => 'Me contacter', 'new_tab' => 'nouvel onglet',
             'tp' => 'Voir mon profil coach TrainingPeaks', 'tp_big' => ['Un suivi sur TrainingPeaks.', 'La plateforme que j\'utilise pour ma propre carrière.'],
             'tp_text' => 'Je planifie et j\'analyse l\'entraînement de mes athlètes sur TrainingPeaks. Vous y retrouvez vos séances, je vois vos données dès la fin de l\'entraînement, et le plan s\'ajuste semaine après semaine.',
             'tp_points' => ['Vos séances dans votre calendrier, envoyées sur votre montre et votre compteur', 'Chaque séance analysée : puissance, allure, fréquence cardiaque', 'Charge, forme et fatigue suivies sur toute la saison'],
             'pmc_title' => 'Trois saisons de charge d\'entraînement',
             'pmc_text' => 'Mon propre PMC (Performance Management Chart), de novembre 2023 à novembre 2026 : les courses, les coupures hivernales, une blessure en juillet 2025. Fin 2025, je change de méthode d\'entraînement : ma charge chronique (CTL) baisse nettement, et pourtant je vais plus vite, avec 7h38 à l\'IRONMAN Brasil et un marathon en 2h37 à Roth. La charge n\'est pas un objectif en soi : ce qui compte, c\'est ce qu\'elle produit le jour de la course.',
             'pmc_legend' => ['Forme (CTL)', 'Fatigue (ATL)', 'Courses longue distance', 'Coupure hivernale', 'Blessure (juillet 2025)'],
             'pmc2_title' => 'Et celui d\'un athlète que j\'accompagne : Geoffroy',
             'pmc2_text' => 'Geoffroy mène une vie professionnelle chargée et court au plus haut niveau amateur : Championnat du monde 70.3 à Taupo en 2024, puis IRONMAN Texas, Championnat du monde 70.3 à Nice et Championnat du monde IRONMAN à Kona en 2026, avant de passer professionnel en 2027. Sa charge reste bien en dessous de la mienne (les deux graphiques sont à la même échelle) : avec un emploi du temps contraint, on cherche le bon entraînement, pas le plus gros.',
             'pmc2_races' => 'Courses', 'pmc2_off' => 'Coupure',
             'h_highlights' => 'Highlights', 'see_gallery' => 'Voir la Galerie', 'h_quotes' => 'Témoignages', 'quotes_big' => ['Ce que disent', 'mes athlètes.'], 'quotes_note' => '', 'all_quotes' => 'Tous les Témoignages', 'prev' => 'Précédent', 'next' => 'Suivant',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Coaching — Arnaud Guilloux', 'kicker' => 'Arnaud Guilloux · Professional triathlete', 'h1' => 'Coaching', 'place' => 'Performance-driven',
             'alt' => 'Arnaud Guilloux running at Challenge Roth', 'cta' => 'Talk about your goal',
             'h_offers' => 'Offers', 'offers_big' => ['What', 'I offer.'], 'card_cta' => 'Get in touch', 'new_tab' => 'opens in a new tab',
             'tp' => 'See my TrainingPeaks coach profile', 'tp_big' => ['Coaching on TrainingPeaks.', 'The platform I use for my own career.'],
             'tp_text' => 'I plan and analyse my athletes\' training on TrainingPeaks. You find your sessions there, I see your data as soon as you finish, and the plan adjusts week after week.',
             'tp_points' => ['Your sessions in your calendar, sent to your watch and bike computer', 'Every session analysed: power, pace, heart rate', 'Load, fitness and fatigue tracked across the whole season'],
             'pmc_title' => 'Three seasons of training load',
             'pmc_text' => 'My own PMC (Performance Management Chart), from November 2023 to November 2026: races, off-season breaks, an injury in July 2025. In late 2025 I changed my training method: my chronic load (CTL) dropped markedly, and yet I race faster, with 7:38 at IRONMAN Brasil and a 2:37 marathon at Roth. Load is not a goal in itself: what matters is what it produces on race day.',
             'pmc_legend' => ['Fitness (CTL)', 'Fatigue (ATL)', 'Long-distance races', 'Off-season break', 'Injury (July 2025)'],
             'pmc2_title' => 'And that of an athlete I coach: Geoffroy',
             'pmc2_text' => 'Geoffroy has a demanding job and races at the top amateur level: the 70.3 World Championship in Taupo in 2024, then IRONMAN Texas, the 70.3 World Championship in Nice and the IRONMAN World Championship in Kona in 2026, before turning professional in 2027. His load stays well below mine (both charts use the same scale): with a tight schedule, we look for the right training, not the biggest.',
             'pmc2_races' => 'Races', 'pmc2_off' => 'Break',
             'h_highlights' => 'Highlights', 'see_gallery' => 'See the Gallery', 'h_quotes' => 'Testimonials', 'quotes_big' => ['What my', 'athletes say.'], 'quotes_note' => 'Testimonials are shown in their original French.', 'all_quotes' => 'All Testimonials', 'prev' => 'Previous', 'next' => 'Next',
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
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css?v=99">
<link rel="icon" href="<?= e($base) ?>assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="<?= e($base) ?>favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e($base) ?>assets/apple-touch-icon.png">
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
        <?php if (($oico = offer_icon_svg((string) ($it['icon'] ?? ''))) !== ''): ?><span class="offer-ico"><?= $oico ?></span><?php endif; ?>
        <h3><?= e(tr($it, 'title')) ?></h3>
        <?php if (tr($it, 'detail') !== ''): ?><p class="offer-detail"><?= e(tr($it, 'detail')) ?></p><?php endif; ?>
        <?php if (tr($it, 'price') !== ''): ?><p class="offer-price"><?= implode('<br>', array_map('e', explode(' · ', tr($it, 'price')))) ?></p><?php endif; ?>
        <a class="card-cta" href="#contact" data-contact="coaching"><?= e($T['card_cta']) ?></a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php
/* TrainingPeaks : la plateforme de suivi, le profil coach (lien réglable dans Réglages) et le PMC de trois saisons.
   Le graphique (assets/img/pmc.svg) est inséré tel quel pour hériter de la police du site. */
$tpUrl  = trim((string) ($settings['trainingpeaks'] ?? ''));
$pmcSvg = is_file(__DIR__ . '/assets/img/pmc.svg') ? (string) file_get_contents(__DIR__ . '/assets/img/pmc.svg') : ''; ?>
<?php if (preg_match('#^https?://#i', $tpUrl)): ?>
<section id="trainingpeaks">
  <div class="wrap">
    <p class="eyebrow">TrainingPeaks</p>
    <h2 class="big"><?= e($T['tp_big'][0]) ?> <span><?= e($T['tp_big'][1]) ?></span></h2>
    <div class="tp-grid">
      <div>
        <p class="feature-text"><?= e($T['tp_text']) ?></p>
        <ul class="tp-points">
          <?php foreach ($T['tp_points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?>
        </ul>
        <a class="btn" href="<?= e($tpUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($T['tp'] . ' — ' . $T['new_tab']) ?>"><?= e($T['tp']) ?></a>
      </div>
      <a class="tp-shot" href="<?= e($tpUrl) ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true">
        <img src="<?= e($base) ?>assets/img/trainingpeaks-coach.jpg" width="1200" height="917" alt="" loading="lazy">
      </a>
    </div>
    <?php if ($pmcSvg !== ''): ?>
    <figure class="pmc-fig">
      <figcaption>
        <h3><?= e($T['pmc_title']) ?></h3>
        <p><?= e($T['pmc_text']) ?></p>
      </figcaption>
      <div class="pmc-scroll" tabindex="0" role="group" aria-label="<?= e($T['pmc_title']) ?>"><?= $pmcSvg ?></div>
      <ul class="pmc-legend">
        <li class="l-ctl"><?= e($T['pmc_legend'][0]) ?></li>
        <li class="l-atl"><?= e($T['pmc_legend'][1]) ?></li>
        <li class="l-race"><?= e($T['pmc_legend'][2]) ?></li>
        <li class="l-off"><?= e($T['pmc_legend'][3]) ?></li>
        <li class="l-inj"><?= e($T['pmc_legend'][4]) ?></li>
      </ul>
    </figure>
    <?php endif; ?>
    <?php /* PMC d'un athlète accompagné (publié avec son accord) : même échelle que le graphique ci-dessus. */
    $pmcAthlete = is_file(__DIR__ . '/assets/img/pmc-geoffroy.svg') ? (string) file_get_contents(__DIR__ . '/assets/img/pmc-geoffroy.svg') : ''; ?>
    <?php if ($pmcAthlete !== ''): ?>
    <figure class="pmc-fig">
      <figcaption>
        <h3><?= e($T['pmc2_title']) ?></h3>
        <p><?= e($T['pmc2_text']) ?></p>
      </figcaption>
      <div class="pmc-scroll" tabindex="0" role="group" aria-label="<?= e($T['pmc2_title']) ?>"><?= $pmcAthlete ?></div>
      <ul class="pmc-legend">
        <li class="l-ctl"><?= e($T['pmc_legend'][0]) ?></li>
        <li class="l-atl"><?= e($T['pmc_legend'][1]) ?></li>
        <li class="l-race"><?= e($T['pmc2_races']) ?></li>
        <li class="l-off"><?= e($T['pmc2_off']) ?></li>
      </ul>
    </figure>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?= highlights_section_html($coachSlides, $lang, $base, $T['h_highlights'], $base . 'galerie.php?type=coach' . ($lang === 'en' ? '&lang=en' : ''), $T['see_gallery'], $T['prev'], $T['next'], 'c') ?>

<?php if ($quotes): ?>
<section id="temoignages">
  <div class="wrap">
    <div class="head-row">
      <div>
        <h2 class="big"><?= e($T['quotes_big'][0]) ?> <span><?= e($T['quotes_big'][1]) ?></span></h2>
      </div>
      <?php if ($moreQuotes): ?>
      <p class="links"><a class="more" href="<?= e($base) ?>temoignages.php<?= $lang === 'en' ? '?lang=en' : '' ?>"><?= e($T['all_quotes']) ?></a></p>
      <?php endif; ?>
    </div>
  </div>
  <div class="carousel qcar" id="qcar" tabindex="0" aria-label="<?= e($T['h_quotes']) ?>">
    <?php foreach ($quotes as $q): ?>
    <?= testimonial_card_html($q, $lang) ?>
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
<script src="<?= e($base) ?>assets/carousel.js?v=2"></script>
<script>
carousel('qcar', 'qdots', 'qprev', 'qnext', '.qcard');
carousel('ccar', 'cdots', 'cprev', 'cnext', '.slide');
</script>
</body>
</html>
