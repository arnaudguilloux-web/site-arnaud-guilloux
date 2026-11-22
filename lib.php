<?php
declare(strict_types=1);

/*
 * Fonctions partagées entre le site public et le back office.
 * Le contenu en ligne vit dans data/content.json (créé par le back office).
 * Tant qu'il n'existe pas, on lit data/content.default.json (versionné).
 */

const DATA_DIR     = __DIR__ . '/data';
const CONTENT_FILE = DATA_DIR . '/content.json';
const DEFAULT_FILE = DATA_DIR . '/content.default.json';

function read_json_file(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/*
 * Le contenu enregistré par le back office prime. Les rubriques qu'il ne
 * contient pas encore (ajoutées depuis par une mise à jour du site) sont
 * complétées avec le contenu par défaut.
 */
function load_content(): array
{
    $default = read_json_file(DEFAULT_FILE) ?? [];
    $saved   = read_json_file(CONTENT_FILE);
    if ($saved === null) {
        return $default;
    }
    foreach ($default as $key => $value) {
        if (!array_key_exists($key, $saved)) {
            $saved[$key] = $value;
        }
    }
    return $saved;
}

function save_content(array $content): bool
{
    $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    if (is_file(CONTENT_FILE)) {
        @copy(CONTENT_FILE, DATA_DIR . '/content.bak.json');
    }
    $tmp = CONTENT_FILE . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, CONTENT_FILE);
}

/* Types d'entrées du calendrier : code => [FR, EN]. Une entrée sans type est une course. */
function event_types(): array
{
    return [
        'race'  => ['Course', 'Race'],
        'camp'  => ['Stage', 'Camp'],
        'event' => ['Événement', 'Event'],
    ];
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Chemin de base du site ("/" à la racine du domaine). */
function base_path(string $scriptDir): string
{
    return rtrim(str_replace('\\', '/', $scriptDir), '/') . '/';
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/* Pictos des tuiles « Chiffres » : clé => [libellé pour le back office, tracés SVG (24 x 24, trait)]. */
function stat_icons(): array
{
    return [
        'chrono'     => ['Chrono', '<circle cx="12" cy="13.5" r="7.5"/><path d="M12 9.5v4l2.6 2M9.5 2.5h5M12 2.5V6M18.6 6.9l1.4-1.4"/>'],
        'worldchamp' => ['Championnat du monde (globe)', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.4 3.8 5.4 3.8 9s-1.3 6.6-3.8 9c-2.5-2.4-3.8-5.4-3.8-9S9.5 5.4 12 3z"/>'],
        'trophy'     => ['Coupe', '<path d="M8 3.5h8V10a4 4 0 0 1-8 0V3.5zM8 5.5H5.5a1 1 0 0 0-1 1V7a3 3 0 0 0 3.2 3M16 5.5h2.5a1 1 0 0 1 1 1V7a3 3 0 0 1-3.2 3M12 14v4M8.5 20.5h7M9.5 18h5"/>'],
        'podium'     => ['Podium', '<path d="M3 20.5h18M3.5 20.5v-7h5.5v7M9 20.5V9.5h6v11M15 20.5v-5h5.5v5M12 3.2l.9 1.8 2 .3-1.45 1.4.35 2-1.8-.95-1.8.95.35-2L9.1 5.3l2-.3z"/>'],
        'top100'     => ['Top 100', '<circle cx="12" cy="12" r="9.5"/><text x="12" y="15.2" text-anchor="middle" font-size="9" font-weight="700" fill="currentColor" stroke="none" font-family="Inter, Arial, sans-serif">100</text>'],
    ];
}

/* Pictos des cartes « Offres » du coaching : clé => [libellé pour le back office, tracés SVG (24 x 24, trait)]. */
function offer_icons(): array
{
    return [
        'plan'   => ['Plan d\'entraînement', '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11.5l2 2 4-4M9 17.5h6"/>'],
        'camp'   => ['Stage', '<path d="M2.5 20.5L12 4l9.5 16.5M12 4v16.5M8.5 20.5l3.5-6.5 3.5 6.5"/>'],
        'test'   => ['Test (effort, mesure)', '<path d="M3 12h4l2.5-6 4 12 2.5-6H21"/>'],
        'bike'   => ['Vélo', '<circle cx="6" cy="16.5" r="3.5"/><circle cx="18" cy="16.5" r="3.5"/><path d="M6 16.5l4-8h5l3 8M10 8.5l3 8M9 8.5H7M15 8.5L14 6h2"/>'],
        'target' => ['Objectif', '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>'],
    ];
}

function offer_icon_svg(string $key): string
{
    $icons = offer_icons();
    if (!isset($icons[$key])) {
        return '';
    }
    return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">' . $icons[$key][1] . '</svg>';
}

function stat_icon_svg(string $key): string
{
    $icons = stat_icons();
    if (!isset($icons[$key])) {
        return '';
    }
    return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false">' . $icons[$key][1] . '</svg>';
}
