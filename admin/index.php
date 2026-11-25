<?php
declare(strict_types=1);
require __DIR__ . '/../lib.php';
require __DIR__ . '/../lib_pto.php';
require __DIR__ . '/../lib_gallery.php';

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
    'hero' => ['label' => 'Accroche', 'type' => 'single', 'fields' => [
        'kicker_fr' => ['Sur-titre (FR)', 'text'],
        'kicker_en' => ['Sur-titre (EN)', 'text'],
        'lead_fr'   => ['Texte d\'accroche (FR)', 'textarea'],
        'lead_en'   => ['Texte d\'accroche (EN)', 'textarea'],
    ]],
    'stats' => ['label' => 'Chiffres', 'type' => 'list', 'title' => 'value_fr', 'sub' => 'label_fr', 'fields' => [
        'value_fr' => ['Chiffre et mot-clé (FR)', 'text'],
        'value_en' => ['Chiffre et mot-clé (EN)', 'text'],
        'label_fr' => ['Légende (FR)', 'text'],
        'label_en' => ['Légende (EN)', 'text'],
        'icon'     => ['Picto', 'select'],
    ]],
    'story' => ['label' => 'Mon histoire', 'type' => 'list', 'title' => 'title_fr', 'sub' => 'year',
        'add' => '+ Ajouter une étape',
        'help' => 'Les étapes de la section « Mon histoire », affichées sous les chiffres de l\'accueil, dans l\'ordre de cette liste. Les 4 premières sont visibles, les suivantes se déplient avec « Lire la suite ». Sans aucune étape, la section et son entrée de menu disparaissent.',
        'fields' => [
        'year'     => ['Année ou période (ex. 2012 ou 2016–2021)', 'text'],
        'title_fr' => ['Titre (FR)', 'text'],
        'title_en' => ['Titre (EN)', 'text'],
        'text_fr'  => ['Texte (FR)', 'textarea'],
        'text_en'  => ['Texte (EN)', 'textarea'],
    ]],
    'results' => ['label' => 'Palmarès', 'type' => 'list', 'title' => 'race', 'sub' => 'date', 'sub2' => 'year', 'eye' => true,
        'add' => '+ Ajouter une course',
        'help' => 'Toutes les courses y sont, y compris les Mondiaux. « + Ajouter » permet de saisir à la main une course absente de PTO. L\'œil à droite de chaque course permet de l\'afficher sur le site ou de la masquer sans la supprimer. « Importer depuis PTO » ajoute les nouvelles courses et met à jour place et temps ; vos lieux, traductions et notes ne sont jamais écrasés.',
        'fields' => [
        'date'     => ['Date de la course (c\'est elle qui ordonne le palmarès)', 'date'],
        'year'     => ['Année (seulement si la date exacte est inconnue)', 'number'],
        'race'     => ['Course', 'text'],
        'race_en'  => ['Course (EN, si le nom diffère)', 'text'],
        'location' => ['Lieu', 'text'],
        'location_en' => ['Lieu (EN, si différent)', 'text'],
        'continent' => ['Continent (filtre du palmarès ; automatique si laissé vide)', 'select_continent'],
        'rank'     => ['Place (1, 2, 3…, DNF ou texte libre)', 'text'],
        'time'     => ['Temps (h:mm:ss, ex. 7:38:52)', 'text'],
        'note_fr'  => ['Note (FR)', 'text'],
        'note_en'  => ['Note (EN)', 'text'],
        'worlds'   => ['Championnat du monde (badge WC)', 'yesno'],
        'pto'      => ['Clé PTO (automatique, ne pas modifier)', 'ro'],
    ]],
    'partners' => ['label' => 'Partenaires', 'type' => 'list', 'title' => 'name', 'sub' => 'role_fr', 'fields' => [
        'name'    => ['Nom', 'text'],
        'url'     => ['Site web (https://…)', 'url'],
        'logo'    => ['Logo (chemin, ex. assets/img/partners/hoka.png)', 'text'],
        'role_fr' => ['Rôle (FR)', 'text'],
        'role_en' => ['Rôle (EN)', 'text'],
    ]],
    'calendar' => ['label' => 'Calendrier', 'type' => 'list', 'title' => 'race', 'sub' => 'date',
        'help' => 'Courses, stages de préparation et événements (tests aéro…). La prochaine course s\'affiche en grand avec un compte à rebours ; tout le reste est dans la liste en dessous.',
        'fields' => [
        'type'     => ['Type', 'select_event_type'],
        'date'     => ['Date (ou date de début)', 'date'],
        'date_end' => ['Date de fin (facultatif : stage de plusieurs jours)', 'date'],
        'race'     => ['Intitulé', 'text'],
        'race_en'  => ['Intitulé (EN, si différent)', 'text'],
        'location' => ['Lieu', 'text'],
        'location_en' => ['Lieu (EN, si différent)', 'text'],
        'format'   => ['Étiquette (IRONMAN, 70.3, Half… facultatif)', 'text'],
        'note_fr'  => ['Note (FR)', 'text'],
        'note_en'  => ['Note (EN)', 'text'],
    ]],
    'gallery' => ['label' => 'Photos', 'type' => 'list', 'title' => 'title_fr', 'sub' => 'date', 'eye' => true,
        'add' => '+ Ajouter une photo',
        'help' => 'Alimente la page Galerie et les carrousels Highlights (Athlète et Coach). Chaque carrousel affiche les 5 photos les plus récentes de son type ; la page Galerie les montre toutes, de la plus récente à la plus ancienne (sans date : à la suite). La photo est redimensionnée (1800 px max.) et compressée automatiquement, et ses données GPS/EXIF sont effacées : envoyez-la telle qu\'elle sort du téléphone ou de l\'appareil, quelques photos à la fois. L\'œil permet de masquer une photo sans la supprimer.',
        'fields' => [
        'image'    => ['Photo (JPEG, PNG ou WebP)', 'image'],
        'title_fr' => ['Titre (FR)', 'text'],
        'title_en' => ['Titre (EN)', 'text'],
        'desc_fr'  => ['Description (FR)', 'text'],
        'desc_en'  => ['Description (EN)', 'text'],
        'type'     => ['Type : alimente le carrousel Athlète (accueil) ou Coach', 'select_gallery_type'],
        'date'     => ['Date de la photo (affichée sur le site)', 'date'],
        'focus'    => ['Cadrage dans le carrousel (utile pour une photo verticale)', 'select_focus'],
    ]],
    'coaching' => ['label' => 'Coaching', 'type' => 'single', 'fields' => [
        'intro_fr' => ['Texte de présentation (FR)', 'textarea'],
        'intro_en' => ['Texte de présentation (EN)', 'textarea'],
    ]],
    'coaching_items' => ['label' => 'Offres coaching', 'type' => 'list', 'title' => 'title_fr', 'sub' => 'price_fr',
        'help' => 'Les lignes affichées dans la section Coaching. Le tarif est un texte libre : « À partir de 190 € », « Sur demande »… Un « · » (point médian entouré d\'espaces) dans le tarif passe à la ligne.',
        'fields' => [
        'title_fr'  => ['Titre (FR)', 'text'],
        'title_en'  => ['Titre (EN)', 'text'],
        'detail_fr' => ['Précision (FR) — facultatif, ex. VO2 et lactate', 'text'],
        'detail_en' => ['Précision (EN) — facultatif', 'text'],
        'price_fr'  => ['Tarif (FR)', 'text'],
        'price_en'  => ['Tarif (EN)', 'text'],
        'icon'      => ['Picto', 'select_offer'],
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
        'url'          => ['Lien du prénom : fiche Triathlon Lab (https://…) — facultatif', 'url'],
    ]],
    'menu' => ['label' => 'Menu', 'type' => 'single',
        'help' => 'La petite phrase affichée sous chaque entrée des sous-menus « L\'Athlète » et « Le Coach ». Les entrées apparaissent toutes seules selon les sections affichées sur le site (pas de palmarès visible = pas d\'entrée Palmarès, etc.) ; seules les phrases se règlent ici. Vide = le titre seul.',
        'fields' => [
        'athlete_top_fr' => ['L\'Athlète › Présentation (FR)', 'text'],
        'athlete_top_en' => ['L\'Athlète › Présentation (EN)', 'text'],
        'athlete_histoire_fr' => ['L\'Athlète › Mon histoire (FR)', 'text'],
        'athlete_histoire_en' => ['L\'Athlète › Mon histoire (EN)', 'text'],
        'athlete_highlights_fr' => ['L\'Athlète › Highlights (FR)', 'text'],
        'athlete_highlights_en' => ['L\'Athlète › Highlights (EN)', 'text'],
        'athlete_palmares_fr' => ['L\'Athlète › Palmarès (FR)', 'text'],
        'athlete_palmares_en' => ['L\'Athlète › Palmarès (EN)', 'text'],
        'athlete_calendrier_fr' => ['L\'Athlète › Calendrier (FR)', 'text'],
        'athlete_calendrier_en' => ['L\'Athlète › Calendrier (EN)', 'text'],
        'athlete_partenaires_fr' => ['L\'Athlète › Partenaires (FR)', 'text'],
        'athlete_partenaires_en' => ['L\'Athlète › Partenaires (EN)', 'text'],
        'athlete_contact_fr' => ['L\'Athlète › Contact (FR)', 'text'],
        'athlete_contact_en' => ['L\'Athlète › Contact (EN)', 'text'],
        'coach_top_fr' => ['Le Coach › Présentation (FR)', 'text'],
        'coach_top_en' => ['Le Coach › Présentation (EN)', 'text'],
        'coach_offres_fr' => ['Le Coach › Offres (FR)', 'text'],
        'coach_offres_en' => ['Le Coach › Offres (EN)', 'text'],
        'coach_trainingpeaks_fr' => ['Le Coach › TrainingPeaks (FR)', 'text'],
        'coach_trainingpeaks_en' => ['Le Coach › TrainingPeaks (EN)', 'text'],
        'coach_highlights_fr' => ['Le Coach › Highlights (FR)', 'text'],
        'coach_highlights_en' => ['Le Coach › Highlights (EN)', 'text'],
        'coach_temoignages_fr' => ['Le Coach › Témoignages (FR)', 'text'],
        'coach_temoignages_en' => ['Le Coach › Témoignages (EN)', 'text'],
        'coach_contact_fr' => ['Le Coach › Contact (FR)', 'text'],
        'coach_contact_en' => ['Le Coach › Contact (EN)', 'text'],
    ]],
    'settings' => ['label' => 'Réglages', 'type' => 'single', 'fields' => [
        'draft'         => ['Site masqué des moteurs de recherche (noindex) : à désactiver au lancement', 'bool'],
        'contact_email' => ['E-mail qui reçoit le formulaire (jamais affiché sur le site)', 'email'],
        'instagram'     => ['Instagram (https://…)', 'url'],
        'strava'        => ['Strava (https://…)', 'url'],
        'facebook'      => ['Facebook (https://…)', 'url'],
        'x'             => ['X / Twitter (https://…)', 'url'],
        'linkedin'      => ['LinkedIn (https://…)', 'url'],
        'ads_url_fr'    => ['Menu « Mes annonces » : lien en français (https://…)', 'url'],
        'ads_url_en'    => ['Menu « My listings » : lien en anglais (https://…)', 'url'],
        'trainingpeaks' => ['Profil coach TrainingPeaks (https://…) : affiché en pied de page et dans la section TrainingPeaks du Coach ; vide = les deux sont masqués', 'url'],
        'siret'         => ['SIRET (mentions légales)', 'text'],
    ]],
];

/* Onglets du back office, dans l'ordre du site : L'Athlète (accueil), Le Coach (page coaching), puis les réglages. */
$GROUPS = ['L\'Athlète' => ['hero', 'stats', 'story', 'results', 'partners', 'calendar'], 'Galerie' => ['gallery'], 'Le Coach' => ['coaching', 'coaching_items', 'testimonials'], 'Site' => ['menu', 'settings']];

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
    } elseif ($type === 'select_event_type') {
        $h .= '<select name="' . e($name) . '">';
        foreach (event_types() as $k => $nm) {
            $sel = $value === $k || ($value === '' && $k === 'race');
            $h  .= '<option value="' . e($k) . '"' . ($sel ? ' selected' : '') . '>' . e($nm[0]) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'select_gallery_type') {
        $h .= '<select name="' . e($name) . '">';
        foreach (gallery_types() as $k => $nm) {
            $h .= '<option value="' . e($k) . '"' . ($value === $k || ($value === '' && $k === 'athlete') ? ' selected' : '') . '>' . e($nm[0]) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'select_focus') {
        $h .= '<select name="' . e($name) . '">';
        foreach (gallery_focuses() as $k => $nm) {
            $h .= '<option value="' . e($k) . '"' . ($value === $k || ($value === '' && $k === '50% 30%') ? ' selected' : '') . '>' . e($nm) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'image') {
        preg_match('/\[([^\]]+)\]/', $name, $im);   /* items[<index>][image] : le fichier envoyé s'appelle photo[<index>] */
        if (gallery_valid_image($value)) {
            $h .= '<img class="thumb" src="../' . e($value) . '" alt="" loading="lazy">';
        }
        $h .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">'
            . '<input type="file" name="photo[' . e($im[1] ?? '') . ']" accept="image/jpeg,image/png,image/webp">';
    } elseif ($type === 'select_continent') {
        $h .= '<select name="' . e($name) . '"><option value="">Automatique</option>';
        foreach (continent_names() as $k => $nm) {
            $h .= '<option value="' . e($k) . '"' . ($value === $k ? ' selected' : '') . '>' . e($nm[0]) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'select' || $type === 'select_offer') {
        $h .= '<select name="' . e($name) . '"><option value="">Aucun</option>';
        foreach ($type === 'select' ? stat_icons() : offer_icons() as $k => $ic) {
            $h .= '<option value="' . e($k) . '"' . ($value === $k ? ' selected' : '') . '>' . e($ic[0]) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'yesno') {
        $h .= '<select name="' . e($name) . '">'
            . '<option value="0"' . ($value !== '1' ? ' selected' : '') . '>Non</option>'
            . '<option value="1"' . ($value === '1' ? ' selected' : '') . '>Oui</option></select>';
    } elseif ($type === 'ro') {
        $h .= '<input type="text" name="' . e($name) . '" value="' . e($value) . '" readonly>';
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
    if ($sub === '' && isset($def['sub2'])) {
        $sub = trim((string) ($item[$def['sub2']] ?? ''));
    }
    $eye   = !empty($def['eye']);
    $on    = (string) ($item['visible'] ?? '1') !== '0';
    $h = '<details class="card' . ($eye ? ' has-eye' : '') . ($eye && !$on ? ' is-off' : '') . '"' . ($open ? ' open' : '') . '><summary><b>'
       . e($title !== '' ? $title : 'Nouvelle entrée') . '</b><small>' . e($sub) . '</small>';
    if ($eye) {
        $h .= '<button type="button" class="eye" data-act="eye" aria-pressed="' . ($on ? 'true' : 'false') . '" aria-label="Afficher sur le site" title="Afficher / masquer sur le site">'
            . '<svg class="eye-on" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>'
            . '<svg class="eye-off" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 5.2A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.1 6.1A17 17 0 0 0 2 12s3.6 7 10 7a10 10 0 0 0 4.1-.9M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg>'
            . '</button><input type="hidden" name="items[' . e($index) . '][visible]" value="' . ($on ? '1' : '0') . '">';
    }
    $h .= '</summary><div class="fields">';
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
$section = (string) ($_GET['s'] ?? 'hero');
if (!isset($SCHEMA[$section])) {
    $section = 'hero';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Envoi trop volumineux pour le serveur (' . ini_get('post_max_size') . ' maximum au total) : rien n\'a été enregistré. Envoyez moins de photos à la fois.', 'err');
        redirect('s=' . rawurlencode((string) ($_GET['s'] ?? 'hero')));
    }
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

    if ($action === 'import_pto' && $logged) {
        $html   = pto_fetch();
        $parsed = $html !== null ? pto_parse($html) : [];
        if ($html === null) {
            flash('Import impossible : la page PTO ne répond pas (ou l\'hébergement bloque la connexion). Réessayez plus tard.', 'err');
        } elseif ($parsed === []) {
            flash('Aucune course lue : la page PTO a peut-être changé de forme. Rien n\'a été modifié.', 'err');
        } else {
            $content = load_content();
            $current = is_array($content['results'] ?? null) ? array_values($content['results']) : [];
            [$merged, $added, $upd] = pto_merge($current, $parsed);
            $content['results'] = $merged;
            if (save_content($content)) {
                flash('Import PTO terminé : ' . $added . ' course(s) ajoutée(s), ' . $upd . ' mise(s) à jour. Vérifiez les lieux vides, puis masquez avec l\'œil ce que vous ne voulez pas afficher.');
            } else {
                flash('Échec de l\'enregistrement (droits d\'écriture sur data/ ?).', 'err');
            }
        }
        redirect('s=results');
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
            $uploadErrors = [];
            $noPhoto = 0;
            foreach (is_array($items) ? $items : [] as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $row   = [];
                $empty = true;
                foreach ($def['fields'] as $fk => $_f) {
                    $v = trim((string) (is_scalar($item[$fk] ?? '') ? ($item[$fk] ?? '') : ''));
                    if ($_f[1] === 'image') {
                        $up = $_FILES['photo'] ?? null;
                        if (is_array($up) && is_array($up['error'] ?? null) && (int) ($up['error'][$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                            [$stored, $why] = gallery_store_upload([
                                'error' => $up['error'][$idx], 'tmp_name' => $up['tmp_name'][$idx] ?? '',
                            ]);
                            if ($stored !== null) {
                                $v = $stored;
                            } else {
                                $uploadErrors[] = '« ' . (string) ($up['name'][$idx] ?? 'photo') . ' » : ' . $why;
                            }
                        }
                        if (!gallery_valid_image($v)) {
                            $v = '';
                        }
                    }
                    if ($_f[1] === 'yesno') {
                        $v = $v === '1' ? '1' : '0';
                    } elseif (!in_array($_f[1], ['select_gallery_type', 'select_focus'], true)) {
                        $empty = $empty && $v === '';
                    }
                    if ($fk === 'type' && $_f[1] === 'select_gallery_type' && !isset(gallery_types()[$v])) {
                        $v = 'athlete';
                    }
                    if ($_f[1] === 'select_focus' && !isset(gallery_focuses()[$v])) {
                        $v = '50% 30%';
                    }
                    if ($fk === 'type' && $key === 'calendar' && !isset(event_types()[$v])) {
                        $v = '';
                    }
                    if ($fk === 'continent' && !isset(continent_names()[$v])) {
                        $v = '';
                    }
                    $row[$fk] = $v;
                }
                if ($key === 'calendar' && ($row['date_end'] ?? '') !== '' && ($row['date_end'] <= ($row['date'] ?? ''))) {
                    $row['date_end'] = '';   // fin avant (ou égale à) le début : ignorée
                }
                if (!$empty) {
                    if ($key === 'results' && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $row['date'], $dm)) {
                        $row['year'] = $dm[1];   // l'année suit toujours la date exacte
                    }
                    if (!empty($def['eye'])) {
                        $row['visible'] = (string) ($item['visible'] ?? '1') === '0' ? '0' : '1';
                    }
                    if ($key === 'gallery' && $row['image'] === '') {
                        $noPhoto++;
                    }
                    $rows[] = $row;
                }
            }
            if ($key === 'calendar') {
                usort($rows, static function ($a, $b) {
                    return strcmp($a['date'], $b['date']);
                });
            }
            if ($key === 'results') {
                $rows = results_sort($rows);
                $undated = count(array_filter($rows, static function ($r) { return results_year($r) === 0; }));
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
            if ($key === 'gallery') {
                gallery_cleanup($rows);
            }
            $extra = '';
            if (!empty($uploadErrors)) {
                $extra .= ' Photo(s) refusée(s) : ' . implode(' ; ', $uploadErrors) . '.';
            }
            if (!empty($noPhoto)) {
                $extra .= ' Attention : ' . $noPhoto . ' entrée(s) sans photo, non affichée(s) sur le site.';
            }
            flash('Enregistré. Le site est à jour.' . $extra . (!empty($undated) ? ' Attention : ' . $undated . ' course(s) sans date ni année, placée(s) tout en bas.' : ''));
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
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="../favicon.ico" sizes="48x48">
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
.tabs .grp { flex:none; align-self:center; padding:4px 8px; margin:0 4px 0 12px; font:500 10px/1 var(--mono); letter-spacing:.08em; text-transform:uppercase; color:var(--paper); background:var(--ink); }
.tabs .grp:first-child { margin-left:8px; }
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
.card.has-eye summary::after { margin-left:0; }
.eye { margin-left:auto; flex:none; width:44px; height:44px; margin-block:-12px; display:inline-flex; align-items:center; justify-content:center; background:none; border:0; cursor:pointer; color:var(--ink); }
.eye .eye-off { display:none; }
.card.is-off { opacity:.6; }
.card.is-off .eye { color:var(--mute); }
.card.is-off .eye .eye-on { display:none; }
.card.is-off .eye .eye-off { display:block; }
.fields { padding:4px 14px 14px; display:grid; gap:14px; border-top:1px solid var(--hair); }
.single { background:var(--paper); border:1px solid var(--ink); padding:16px 14px; display:grid; gap:14px; }
label { display:grid; gap:6px; }
label span { font:500 11px/1.3 var(--mono); letter-spacing:.05em; text-transform:uppercase; color:var(--mute); }
img.thumb { display:block; width:100%; max-height:220px; object-fit:contain; background:var(--tint); border:1px solid var(--hair); }
input[type=file] { padding:9px 10px; min-height:0; font-size:14px; }
input[readonly] { background:var(--tint); color:var(--mute); }
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
  <?php foreach ($GROUPS as $gLabel => $gKeys): ?>
  <span class="grp"><?= e($gLabel) ?></span>
  <?php foreach ($gKeys as $key): ?>
  <a href="?s=<?= e($key) ?>"<?= $key === $section ? ' aria-current="page"' : '' ?>><?= e($SCHEMA[$key]['label']) ?></a>
  <?php endforeach; endforeach; ?>
</nav>
<main>
  <h1><?= e($def['label']) ?></h1>
  <?php if ($flash): ?><p class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></p><?php endif; ?>

  <?php if ($section === 'results'): ?>
  <form method="post" class="import" onsubmit="return confirm('Importer depuis PTO ? Les modifications non enregistrées de cette page seront perdues.');">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="import_pto">
    <button type="submit" class="add">⟳ Importer / mettre à jour depuis PTO</button>
  </form>
  <?php endif; ?>

  <form method="post" id="form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="section" value="<?= e($section) ?>">

    <?php if ($def['type'] === 'list'): ?>
    <p class="help"><?= isset($def['help']) ? e($def['help']) . ' ' : '' ?><?= $section === 'calendar'
        ? 'Les courses sont triées par date à l\'enregistrement ; les courses passées disparaissent du site automatiquement.'
        : ($section === 'results'
            ? 'Le palmarès est trié par année puis par date à l\'enregistrement.'
            : ($section === 'gallery'
                ? 'Le site ordonne les photos par date (la plus récente d\'abord) ; ↑ ↓ ne change que l\'ordre des photos sans date.'
                : 'L\'ordre affiché ici est celui du site. Utilisez ↑ ↓ pour réordonner.')) ?></p>
    <button type="button" class="add" id="add"><?= e($def['add'] ?? '+ Ajouter') ?></button>
    <div id="list">
      <?php foreach (array_values($data) as $i => $item): ?>
      <?= card((string) $i, $def, is_array($item) ? $item : [], false) ?>
      <?php endforeach; ?>
    </div>
    <template id="tpl"><?= card('__i__', $def, [], true) ?></template>
    <?php else: ?>
    <?php if (isset($def['help'])): ?><p class="help"><?= e($def['help']) ?></p><?php endif; ?>
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
    if (act === 'eye') {
      e.preventDefault();
      var vis = card.querySelector('input[name$="[visible]"]'), off = vis.value !== '0';
      vis.value = off ? '0' : '1';
      card.classList.toggle('is-off', off);
      btn.setAttribute('aria-pressed', off ? 'false' : 'true');
      dirty = true;
    } else if (act === 'del') {
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
