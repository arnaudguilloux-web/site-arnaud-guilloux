<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

/*
 * Page Contact (GET) et traitement du formulaire de contact (POST).
 * POST : envoie un e-mail à l'adresse réglée dans le back office, puis renvoie vers la page d'où vient
 * le formulaire (accueil par défaut, page Contact si le champ caché « from » vaut « contact »).
 */

$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/contact.php')));

function done(string $back, bool $ok): void
{
    header('Location: ' . $back . 'sent=' . ($ok ? '1' : '0') . '#contact', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $lang = (($_POST['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
    $back = (($_POST['from'] ?? '') === 'contact')
        ? $base . 'contact.php?' . ($lang === 'en' ? 'lang=en&' : '')
        : $base . ($lang === 'en' ? 'en/' : '') . '?';

    /* Anti-spam : champ piège + délai minimum. On répond « envoyé » sans rien envoyer. */
    $ts = (int) ($_POST['ts'] ?? 0);
    if (trim((string) ($_POST['website'] ?? '')) !== '' || $ts === 0 || time() - $ts < 3) {
        done($back, true);
    }

    $clean = static function ($v, int $max): string {
        $v = trim(str_replace(["\r", "\n", "\0"], ' ', is_scalar($v) ? (string) $v : ''));
        return substr($v, 0, $max);
    };
    $name    = $clean($_POST['name'] ?? '', 120);
    $email   = $clean($_POST['email'] ?? '', 160);
    $topic   = (string) ($_POST['topic'] ?? 'other');
    $message = substr(trim(is_scalar($_POST['message'] ?? '') ? (string) $_POST['message'] : ''), 0, 5000);
    $topics  = ['coaching' => 'Coaching / tests / bike fitting', 'sponsoring' => 'Partenariat', 'other' => 'Autre'];
    $topicLabel = $topics[$topic] ?? $topics['other'];

    $settings = load_content()['settings'] ?? [];
    $to       = trim((string) ($settings['contact_email'] ?? ''));

    if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        done($back, false);
    }

    $host = preg_replace('/^www\./', '', preg_replace('/[^a-z0-9.\-]/i', '', explode(':', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'))[0]));
    $enc  = static function (string $s): string {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    };

    $subject = $enc('[Site] ' . $topicLabel . ' — ' . $name);
    $body    = "Nom : $name\nE-mail : $email\nSujet : $topicLabel\nLangue : $lang\n\n$message\n";
    $headers = implode("\r\n", [
        'From: ' . $enc('Site Arnaud Guilloux') . ' <no-reply@' . $host . '>',
        'Reply-To: ' . $email,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);

    done($back, @mail($to, $subject, $body, $headers));
}

/* ---------- Page Contact ---------- */
require_once __DIR__ . '/lib_view.php';
require_once __DIR__ . '/lib_social.php';

$lang = (($_GET['lang'] ?? '') === 'en') ? 'en' : 'fr';
$home = ['fr' => $base, 'en' => $base . 'en/'];
$self = ['fr' => $base . 'contact.php', 'en' => $base . 'contact.php?lang=en'];
$host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$origin = (is_https() ? 'https://' : 'http://') . $host;
$c = load_content();
$settings = $c['settings'] ?? [];
$draft = ($settings['draft'] ?? '1') === '1';
$sent  = isset($_GET['sent']) ? (string) $_GET['sent'] : null;
$topic = (string) ($_GET['topic'] ?? '');

$T = [
    'fr' => ['title' => 'Contact — Arnaud Guilloux',
             'description' => 'Contacter Arnaud Guilloux, triathlète professionnel et coach : coaching, tests physiologiques, bike fitting, partenariats.',
             'legal' => 'Éditeur : Arnaud Guilloux, Saint-Renan (29). Hébergement : OVH SAS, 2 rue Kellermann, 59100 Roubaix.'],
    'en' => ['title' => 'Contact — Arnaud Guilloux',
             'description' => 'Get in touch with Arnaud Guilloux, professional triathlete and coach: coaching, physiological testing, bike fitting, partnerships.',
             'legal' => 'Publisher: Arnaud Guilloux, Saint-Renan, France. Hosting: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.'],
][$lang];
$F = contact_strings($lang);
$socials = social_links($settings);
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($T['title']) ?></title>
<meta name="description" content="<?= e($T['description']) ?>">
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
<?= site_header($lang, $home, $self, $c, 'contact') ?>

<main>
<section class="gal-page" id="contact">
  <div class="wrap contact">
    <div>
      <h1><?= e($F['h_contact']) ?></h1>
      <p class="feature-text"><?= e($F['contact_lead']) ?></p>
      <?= social_cards_html($socials, $base, $lang) ?>
    </div>
    <?= contact_form_html($F, $lang, $base, $sent, true, $topic, 'contact') ?>
  </div>
</section>
</main>

<?= site_footer($T['legal'], $settings, $lang) ?>
</body>
</html>
