<?php
declare(strict_types=1);
require __DIR__ . '/../lib.php';

/*
 * Back office léger, pensé pour le téléphone.
 * - Mot de passe : créé à la première visite, stocké haché dans data/admin.php.
 *   Pour le réinitialiser : supprimer data/admin.php par FTP puis revenir ici.
 * - Contenu : écrit dans data/content.json (sauvegarde précédente dans content.bak.json).
 */

const CONFIG_FILE   = DATA_DIR . '/admin.php';
const THROTTLE_FILE = DATA_DIR . '/throttle.json';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

session_name('ag_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => base_path(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'))),
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Schéma des rubriques éditables ---------- */
$SCHEMA = [
    'calendar' => ['label' => 'Calendrier', 'type' => 'list', 'title' => 'race', 'sub' => 'date', 'fields' => [
        'date'     => ['Date', 'date'],
        'race'     => ['Course', 'text'],
        'race_en'  => ['Course (EN, si le nom diffère)', 'text'],
        'location' => ['Lieu', 'text'],
        'location_en' => ['Lieu (EN, si différent)', 'text'],
        'format'   => ['Format (IRONMAN, 70.3, Half…)', 'text'],
        'note_fr'  => ['Note (FR)', 'text'],
        'note_en'  => ['Note (EN)', 'text'],
    ]],
    'results' => ['label' => 'Palmarès', 'type' => 'list', 'title' => 'race', 'sub' => 'year', 'fields' => [
        'year'     => ['Année', 'number'],
        'race'     => ['Course', 'text'],
        'race_en'  => ['Course (EN, si le nom diffère)', 'text'],
        'location' => ['Lieu', 'text'],
        'location_en' => ['Lieu (EN, si différent)', 'text'],
        'rank'     => ['Place (1, 2, 3… ou texte libre)', 'text'],
        'note_fr'  => ['Note (FR)', 'text'],
        'note_en'  => ['Note (EN)', 'text'],
    ]],
    'qualifications' => ['label' => 'Mondiaux (affichés dans le palmarès)', 'type' => 'list', 'title' => 'event', 'sub' => 'year', 'fields' => [
        'year'     => ['Année', 'number'],
        'event'    => ['Épreuve', 'text'],
        'event_en' => ['Épreuve (EN, si le nom diffère)', 'text'],
        'location' => ['Lieu', 'text'],
        'location_en' => ['Lieu (EN, si différent)', 'text'],
        'note_fr'  => ['Note (FR)', 'text'],
        'note_en'  => ['Note (EN)', 'text'],
    ]],
    'coaching' => ['label' => 'Coaching', 'type' => 'single', 'fields' => [
        'intro_fr' => ['Texte de présentation (FR)', 'textarea'],
        'intro_en' => ['Texte de présentation (EN)', 'textarea'],
    ]],
    'coaching_items' => ['label' => 'Offres coaching', 'type' => 'list', 'title' => 'title_fr', 'sub' => 'price_fr',
        'help' => 'Les lignes affichées dans la section Coaching. Le tarif est un texte libre : « À partir de 190 € », « Sur demande »…',
        'fields' => [
        'title_fr'  => ['Titre (FR)', 'text'],
        'title_en'  => ['Titre (EN)', 'text'],
        'detail_fr' => ['Précision (FR) — facultatif, ex. VO2 et lactate', 'text'],
        'detail_en' => ['Précision (EN) — facultatif', 'text'],
        'price_fr'  => ['Tarif (FR)', 'text'],
        'price_en'  => ['Tarif (EN)', 'text'],
    ]],
    'testimonials' => ['label' => 'Témoignages', 'type' => 'list', 'title' => 'name', 'sub' => 'city',
        'help' => 'Chaque carte : un fait marquant en gros (ex. « 9h06 »), son contexte, puis la citation telle que l\'athlète l\'a écrite (un extrait, on ne réécrit pas ses mots). Signature affichée : « Prénom – Ville » (ville facultative). Ne publier qu\'avec l\'accord de l\'athlète.',
        'fields' => [
        'highlight_fr' => ['Fait marquant en gros (FR) — ex. 9h06', 'text'],
        'highlight_en' => ['Fait marquant en gros (EN)', 'text'],
        'context_fr'   => ['Contexte (FR) — ex. IRONMAN Texas', 'text'],
        'context_en'   => ['Contexte (EN)', 'text'],
        'quote'        => ['Citation (extrait, sans guillemets)', 'textarea'],
        'name'         => ['Prénom', 'text'],
        'city'         => ['Ville (facultatif)', 'text'],
    ]],
    'partners' => ['label' => 'Partenaires', 'type' => 'list', 'title' => 'name', 'sub' => 'role_fr', 'fields' => [
        'name'    => ['Nom', 'text'],
        'url'     => ['Site web (https://…)', 'url'],
        'role_fr' => ['Rôle (FR)', 'text'],
        'role_en' => ['Rôle (EN)', 'text'],
    ]],
    'stats' => ['label' => 'Chiffres', 'type' => 'list', 'title' => 'value', 'sub' => 'label_fr', 'fields' => [
        'value'    => ['Chiffre', 'text'],
        'label_fr' => ['Légende (FR)', 'text'],
        'label_en' => ['Légende (EN)', 'text'],
        'icon'     => ['Picto', 'select'],
    ]],
    'hero' => ['label' => 'Accroche', 'type' => 'single', 'fields' => [
        'kicker_fr' => ['Sur-titre (FR)', 'text'],
        'kicker_en' => ['Sur-titre (EN)', 'text'],
        'lead_fr'   => ['Texte d\'accroche (FR)', 'textarea'],
        'lead_en'   => ['Texte d\'accroche (EN)', 'textarea'],
    ]],
    'settings' => ['label' => 'Réglages', 'type' => 'single', 'fields' => [
        'draft'         => ['Site masqué des moteurs de recherche (noindex) : à désactiver au lancement', 'bool'],
        'contact_email' => ['E-mail qui reçoit le formulaire (jamais affiché sur le site)', 'email'],
        'instagram'     => ['Instagram (https://…)', 'url'],
        'strava'        => ['Strava (https://…)', 'url'],
        'facebook'      => ['Facebook (https://…)', 'url'],
        'x'             => ['X / Twitter (https://…)', 'url'],
        'linkedin'      => ['LinkedIn (https://…)', 'url'],
        'siret'         => ['SIRET (mentions légales)', 'text'],
    ]],
];

/* ---------- Outils ---------- */
function flash(string $msg, string $kind = 'ok'): void
{
    $_SESSION['flash'] = [$msg, $kind];
}

function redirect(string $query = ''): void
{
    header('Location: ./' . ($query !== '' ? '?' . $query : ''), true, 303);
    exit;
}

function throttle_get(): array
{
    $d = is_file(THROTTLE_FILE) ? json_decode((string) file_get_contents(THROTTLE_FILE), true) : null;
    return is_array($d) ? $d + ['fails' => 0, 'until' => 0] : ['fails' => 0, 'until' => 0];
}

function throttle_set(array $state): void
{
    @file_put_contents(THROTTLE_FILE, json_encode($state), LOCK_EX);
}

function field(string $name, array $def, string $value): string
{
    [$label, $type] = $def;
    $h = '<label><span>' . e($label) . '</span>';
    if ($type === 'textarea') {
        $h .= '<textarea name="' . e($name) . '" rows="4">' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        $h .= '<select name="' . e($name) . '"><option value="">Aucun</option>';
        foreach (stat_icons() as $k => $ic) {
            $h .= '<option value="' . e($k) . '"' . ($value === $k ? ' selected' : '') . '>' . e($ic[0]) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'bool') {
        $h .= '<select name="' . e($name) . '">'
            . '<option value="1"' . ($value === '1' ? ' selected' : '') . '>Affiché</option>'
            . '<option value="0"' . ($value !== '1' ? ' selected' : '') . '>Masqué</option></select>';
    } else {
        $extra = $type === 'number' ? ' inputmode="numeric"' : '';
        $htmlType = $type === 'number' ? 'text' : $type;
        $h .= '<input type="' . $htmlType . '" name="' . e($name) . '" value="' . e($value) . '"' . $extra . '>';
    }
    return $h . '</label>';
}

function card(string $index, array $def, array $item, bool $open): string
{
    $title = trim((string) ($item[$def['title']] ?? ''));
    $sub   = trim((string) ($item[$def['sub']] ?? ''));
    $h = '<details class="card"' . ($open ? ' open' : '') . '><summary><b>' . e($title !== '' ? $title : 'Nouvelle entrée')
       . '</b><small>' . e($sub) . '</small></summary><div class="fields">';
    foreach ($def['fields'] as $key => $f) {
        $h .= field('items[' . $index . '][' . $key . ']', $f, (string) ($item[$key] ?? ''));
    }
    return $h . '<div class="row-actions">'
        . '<button type="button" data-act="up" aria-label="Monter">↑</button>'
        . '<button type="button" data-act="down" aria-label="Descendre">↓</button>'
        . '<button type="button" data-act="del" class="danger">Supprimer</button>'
        . '</div></div></details>';
}

/* ---------- Actions ---------- */
$config  = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : null;
$hash    = is_array($config) ? (string) ($config['hash'] ?? '') : '';
$logged  = $hash !== '' && !empty($_SESSION['auth']);
$section = (string) ($_GET['s'] ?? 'calendar');
if (!isset($SCHEMA[$section])) {
    $section = 'calendar';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        flash('Session expirée, merci de réessayer.', 'err');
        redirect();
    }
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'setup' && $hash === '') {
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 10) {
            flash('Choisissez un mot de passe d\'au moins 10 caractères.', 'err');
        } elseif ($pw !== (string) ($_POST['password2'] ?? '')) {
            flash('Les deux mots de passe ne correspondent pas.', 'err');
        } else {
            $php = "<?php\nreturn " . var_export(['hash' => password_hash($pw, PASSWORD_DEFAULT)], true) . ";\n";
            if (file_put_contents(CONFIG_FILE, $php, LOCK_EX) === false) {
                flash('Impossible d\'écrire dans le dossier data/ (droits d\'écriture ?).', 'err');
            } else {
                session_regenerate_id(true);
                $_SESSION['auth'] = true;
                flash('Mot de passe enregistré. Bienvenue.');
            }
        }
        redirect();
    }

    if ($action === 'login' && $hash !== '') {
        $t = throttle_get();
        if ($t['until'] > time()) {
            flash('Trop de tentatives. Réessayez dans ' . (int) ceil(($t['until'] - time()) / 60) . ' min.', 'err');
            redirect();
        }
        if (password_verify((string) ($_POST['password'] ?? ''), $hash)) {
            throttle_set(['fails' => 0, 'until' => 0]);
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
        } else {
            usleep(700000);
            $fails = (int) $t['fails'] + 1;
            throttle_set($fails >= 5 ? ['fails' => 0, 'until' => time() + 900] : ['fails' => $fails, 'until' => 0]);
            flash('Mot de passe incorrect.', 'err');
        }
        redirect();
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect();
    }

    if ($action === 'save' && $logged) {
        $key = (string) ($_POST['section'] ?? '');
        if (!isset($SCHEMA[$key])) {
            redirect();
        }
        $def     = $SCHEMA[$key];
        $content = load_content();

        if ($def['type'] === 'list') {
            $rows  = [];
            $items = $_POST['items'] ?? [];
            foreach (is_array($items) ? $items : [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $row   = [];
                $empty = true;
                foreach ($def['fields'] as $fk => $_f) {
                    $v = trim((string) (is_scalar($item[$fk] ?? '') ? ($item[$fk] ?? '') : ''));
                    $row[$fk] = $v;
                    $empty = $empty && $v === '';
                }
                if (!$empty) {
                    $rows[] = $row;
                }
            }
            if ($key === 'calendar') {
                usort($rows, static function ($a, $b) {
                    return strcmp($a['date'], $b['date']);
                });
            }
            $content[$key] = $rows;
        } else {
            $posted = $_POST['f'] ?? [];
            foreach ($def['fields'] as $fk => $_f) {
                $v = is_array($posted) && is_scalar($posted[$fk] ?? null) ? trim((string) $posted[$fk]) : '';
                $content[$key][$fk] = $v;
            }
        }

        if (save_content($content)) {
            flash('Enregistré. Le site est à jour.');
        } else {
            flash('Échec de l\'enregistrement (droits d\'écriture sur data/ ?).', 'err');
        }
        redirect('s=' . rawurlencode($key));
    }

    redirect();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$csrf = $_SESSION['csrf'];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Back office — Arnaud Guilloux</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root { --ink:#0a0a0a; --paper:#fff; --tint:#f3f3f1; --mute:#6a6a6a; --hair:#d6d6d6; --mono:"IBM Plex Mono",ui-monospace,Consolas,monospace; }
*,*::before,*::after { box-sizing:border-box; }
body { margin:0; background:var(--tint); color:var(--ink); font:400 16px/1.45 "Archivo","Helvetica Neue",Arial,sans-serif; -webkit-text-size-adjust:100%; }
.bar { position:sticky; top:0; z-index:5; background:var(--ink); color:var(--paper); display:flex; align-items:center; gap:12px; padding:0 16px; height:52px; }
.bar b { font-weight:800; font-stretch:112%; text-transform:uppercase; font-size:14px; margin-right:auto; }
.bar a, .bar button { font:500 12px/1 var(--mono); letter-spacing:.05em; text-transform:uppercase; color:inherit; background:none; border:1px solid rgba(255,255,255,.5); padding:0 10px; height:34px; display:inline-flex; align-items:center; text-decoration:none; cursor:pointer; border-radius:0; }
.tabs { display:flex; gap:0; overflow-x:auto; background:var(--paper); border-bottom:1px solid var(--ink); scrollbar-width:none; }
.tabs::-webkit-scrollbar { display:none; }
.tabs a { flex:none; padding:15px 14px; font:500 12px/1 var(--mono); letter-spacing:.05em; text-transform:uppercase; text-decoration:none; color:var(--mute); border-bottom:3px solid transparent; }
.tabs a[aria-current] { color:var(--ink); border-bottom-color:var(--ink); }
main { max-width:680px; margin:0 auto; padding:16px 16px 110px; }
h1 { font-weight:800; font-stretch:112%; text-transform:uppercase; font-size:26px; line-height:1; margin:10px 0 16px; }
p.help { color:var(--mute); font-size:14px; margin:0 0 16px; }
.flash { border:1px solid var(--ink); background:var(--paper); padding:12px 14px; margin:0 0 16px; font-weight:600; }
.flash.err { background:var(--ink); color:var(--paper); }
.card { background:var(--paper); border:1px solid var(--ink); margin-bottom:10px; }
.card summary { display:flex; align-items:baseline; gap:10px; padding:15px 14px; cursor:pointer; list-style:none; min-height:52px; }
.card summary::-webkit-details-marker { display:none; }
.card summary::after { content:"+"; margin-left:auto; font:500 18px/1 var(--mono); }
.card[open] summary::after { content:"–"; }
.card summary b { font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; }
.card summary small { font:400 12px/1 var(--mono); color:var(--mute); white-space:nowrap; }
.fields { padding:4px 14px 14px; display:grid; gap:14px; border-top:1px solid var(--hair); }
.single { background:var(--paper); border:1px solid var(--ink); padding:16px 14px; display:grid; gap:14px; }
label { display:grid; gap:6px; }
label span { font:500 11px/1.3 var(--mono); letter-spacing:.05em; text-transform:uppercase; color:var(--mute); }
input, textarea, select { width:100%; font:400 16px/1.4 inherit; font-family:inherit; color:var(--ink); background:var(--paper); border:1px solid var(--ink); border-radius:0; padding:11px 12px; min-height:46px; }
textarea { resize:vertical; }
input:focus, textarea:focus, select:focus { outline:2px solid var(--ink); outline-offset:1px; }
button { font-family:inherit; }
.row-actions { display:flex; gap:8px; }
.row-actions button { height:44px; min-width:48px; padding:0 14px; background:var(--paper); border:1px solid var(--ink); font:500 13px/1 var(--mono); cursor:pointer; border-radius:0; color:var(--ink); }
.row-actions .danger { margin-left:auto; text-transform:uppercase; letter-spacing:.05em; font-size:12px; }
.add { width:100%; height:50px; margin-bottom:14px; background:var(--paper); border:1px dashed var(--ink); font:500 13px/1 var(--mono); letter-spacing:.05em; text-transform:uppercase; cursor:pointer; color:var(--ink); border-radius:0; }
.savebar { position:fixed; left:0; right:0; bottom:0; background:var(--paper); border-top:1px solid var(--ink); padding:10px 16px calc(10px + env(safe-area-inset-bottom)); }
.savebar button, .primary { display:block; width:100%; max-width:648px; margin:0 auto; height:52px; background:var(--ink); color:var(--paper); border:0; font:500 14px/1 var(--mono); letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:0; }
.auth { max-width:420px; margin:10vh auto 0; padding:0 16px; }
.auth form { background:var(--paper); border:1px solid var(--ink); padding:20px 16px; display:grid; gap:16px; }
</style>
</head>
<body>
<?php if (!$logged): ?>
<div class="auth">
  <h1><?= $hash === '' ? 'Créer le mot de passe' : 'Back office' ?></h1>
  <?php if ($flash): ?><p class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($hash === ''): ?>
    <input type="hidden" name="action" value="setup">
    <p class="help">Première visite : choisissez le mot de passe du back office (10 caractères minimum).</p>
    <label><span>Mot de passe</span><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
    <label><span>Confirmer</span><input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
    <button class="primary" type="submit">Enregistrer</button>
    <?php else: ?>
    <input type="hidden" name="action" value="login">
    <label><span>Mot de passe</span><input type="password" name="password" required autofocus autocomplete="current-password"></label>
    <button class="primary" type="submit">Se connecter</button>
    <?php endif; ?>
  </form>
</div>
<?php else:
    $def     = $SCHEMA[$section];
    $content = load_content();
    $data    = $content[$section] ?? [];
    if (!is_array($data)) {
        $data = [];
    }
?>
<div class="bar">
  <b>Back office</b>
  <a href="../" target="_blank" rel="noopener">Voir le site</a>
  <form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="logout"><button type="submit">Quitter</button></form>
</div>
<nav class="tabs">
  <?php foreach ($SCHEMA as $key => $s): ?>
  <a href="?s=<?= e($key) ?>"<?= $key === $section ? ' aria-current="page"' : '' ?>><?= e($s['label']) ?></a>
  <?php endforeach; ?>
</nav>
<main>
  <h1><?= e($def['label']) ?></h1>
  <?php if ($flash): ?><p class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></p><?php endif; ?>

  <form method="post" id="form">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="section" value="<?= e($section) ?>">

    <?php if ($def['type'] === 'list'): ?>
    <p class="help"><?= isset($def['help']) ? e($def['help']) . ' ' : '' ?><?= $section === 'calendar'
        ? 'Les courses sont triées par date à l\'enregistrement ; les courses passées disparaissent du site automatiquement.'
        : 'L\'ordre affiché ici est celui du site. Utilisez ↑ ↓ pour réordonner.' ?></p>
    <button type="button" class="add" id="add">+ Ajouter</button>
    <div id="list">
      <?php foreach (array_values($data) as $i => $item): ?>
      <?= card((string) $i, $def, is_array($item) ? $item : [], false) ?>
      <?php endforeach; ?>
    </div>
    <template id="tpl"><?= card('__i__', $def, [], true) ?></template>
    <?php else: ?>
    <div class="single">
      <?php foreach ($def['fields'] as $key => $f): ?>
      <?= field('f[' . $key . ']', $f, (string) ($data[$key] ?? '')) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="savebar"><button type="submit">Enregistrer</button></div>
  </form>
</main>
<script>
(function () {
  var list = document.getElementById('list'), tpl = document.getElementById('tpl'), add = document.getElementById('add');
  var form = document.getElementById('form'), dirty = false, n = 0;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  if (!list) return;
  add.addEventListener('click', function () {
    list.insertAdjacentHTML('afterbegin', tpl.innerHTML.split('__i__').join('n' + (n++)));
    var first = list.firstElementChild.querySelector('input, textarea');
    if (first) first.focus();
    dirty = true;
  });
  list.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-act]');
    if (!btn) return;
    var card = btn.closest('.card'), act = btn.dataset.act;
    if (act === 'del') {
      if (confirm('Supprimer cette entrée ?')) { card.remove(); dirty = true; }
    } else if (act === 'up' && card.previousElementSibling) {
      list.insertBefore(card, card.previousElementSibling); dirty = true;
    } else if (act === 'down' && card.nextElementSibling) {
      list.insertBefore(card.nextElementSibling, card); dirty = true;
    }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
