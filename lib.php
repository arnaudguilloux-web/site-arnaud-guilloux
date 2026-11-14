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

function load_content(): array
{
    foreach ([CONTENT_FILE, DEFAULT_FILE] as $file) {
        if (is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }
    }
    return [];
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
