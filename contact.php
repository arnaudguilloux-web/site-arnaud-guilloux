<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

/* Traitement du formulaire de contact : envoie un e-mail à l'adresse réglée dans le back office. */

$base = base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/contact.php')));
$lang = (($_POST['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
$back = $base . ($lang === 'en' ? 'en/' : '');

function done(string $back, bool $ok): void
{
    header('Location: ' . $back . '?sent=' . ($ok ? '1' : '0') . '#contact', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $back, true, 303);
    exit;
}

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
