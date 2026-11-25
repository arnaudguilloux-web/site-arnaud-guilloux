# Site Arnaud Guilloux

Site bilingue (FR `/`, EN `/en/`) en PHP pur, sans dépendance ni build, pour hébergement mutualisé OVH. Deux univers : L'Athlète (`index.php`) et Le Coach (`coaching.php`). Le détail fonctionnel (pages, back office, mise en ligne, ajout de photo) est dans `README.md` : le lire avant de modifier.

## Lancer en local

```bash
php -S localhost:8081
```

Pas de tests ni de linter. Vérifier avec `php -l <fichier>` puis en ouvrant les pages en FR et en EN (`/en/` ou `?lang=en`). Le serveur PHP intégré ignore `.htaccess` : les règles de réécriture et d'accès ne se testent qu'en ligne.

## Architecture

- Chaque page publique (`index`, `coaching`, `galerie`, `palmares`, `temoignages`) est un script autonome : détermine `$lang` et `$base`, charge `load_content()`, définit son tableau `$T` (textes fixes FR/EN), puis écrit le HTML. Les en-têtes `<head>` sont dupliqués d'une page à l'autre.
- `lib.php` : chargement/sauvegarde du contenu, `e()` (échappement), pictos SVG des chiffres et des offres, types d'événements.
- `lib_view.php` : blocs partagés (`site_header`, `site_footer`, contact, témoignages, carrousel Highlights, ligne de palmarès, calendrier). Plusieurs fonctions (`tr()`, `loc()`, `fmt_time()`, `rank_label()`, `result_li()`) lisent la variable **globale `$lang`** : la page doit la définir avant de les appeler. Les textes du menu et du formulaire de contact FR/EN sont aussi dans ce fichier.
- `lib_gallery.php` : `gallery_items($c, $type)` (photos de la rubrique `gallery` du contenu, triées par date décroissante), légendes/dates, `gallery_store_upload()` (redimensionne et ré-encode en JPEG). Photos téléversées dans `assets/gallery/` (ignoré par git et exclu du déploiement) ; photos historiques dans `assets/img/`.
- `lib_pto.php` : import du palmarès PTO, lieux et continents des courses connues, tri du palmarès. Chargé à la demande par `results_rows()` (et par l'admin).
- `lib_social.php` : réseaux sociaux.
- `contact.php` : en GET, la page Contact (lien « Contact » du menu) ; en POST, traite le formulaire (anti-spam par champ piège + délai, puis `mail()` vers l'adresse des Réglages).
- `admin/index.php` : back office mobile, un seul fichier, styles en ligne. Les rubriques éditables sont décrites dans le tableau `$SCHEMA` (ajouter un champ = l'ajouter là ; ajouter une rubrique = aussi l'ajouter à `$GROUPS` pour l'onglet).
- `assets/` : `style.css` (variables dans `:root`), JS vanilla (`nav.js`, `carousel.js`, `hero.js`, `lightbox.js`, `contact.js`), `img/` et `img/partners/`.

## Contenu et données

- `data/content.default.json` est versionné ; `data/content.json` (écrit par le back office) ne l'est pas et **prime** sur le défaut rubrique par rubrique. Modifier le défaut ne change donc pas une rubrique déjà enregistrée en ligne.
- Ne jamais versionner ni écraser `data/content.json`, `content.bak.json`, `admin.php` (hash du mot de passe), `throttle.json`. `data/.htaccess` interdit tout accès direct au dossier.
- Les champs bilingues suivent le schéma `clé_fr` / `clé_en` ; `tr()` et `loc()` retombent sur le français si l'anglais est vide.
- Le site est en `noindex` tant que le réglage `draft` vaut `1` (valeur par défaut).

## Conventions

- Textes visibles du site public en FR et EN. Exceptions assumées : le back office (français seul), les citations de témoignages (français d'origine, une note l'indique en EN), les e-mails du formulaire.
- `declare(strict_types=1);` en tête de chaque fichier PHP ; échapper toute sortie HTML avec `e()`.
- PHP 7.4 minimum : pas de `match`, propriétés promues, `str_contains`, `?->`… Les closures s'écrivent `static function (...) {}`.
- **Cache-busting** : les assets sont appelés avec `?v=N` (`style.css?v=94` répété dans les 5 pages, `nav.js?v=5` et `contact.js?v=1` dans `lib_view.php`, `carousel.js?v=2`, `hero.js?v=1`, `lightbox.js?v=1`). Après toute modification d'un asset, incrémenter son `v` partout où il apparaît (`grep -rn 'style.css?v=' *.php`).
- `.htaccess` : le `FilesMatch` ne refuse que `lib.php`, `README.md`, `CLAUDE.md`, `STATUS.md` et `.gitignore`. Les autres `lib_*.php` restent accessibles (ils ne produisent aucune sortie). Tout nouveau fichier interne non destiné au public (note, doc…) doit être ajouté à ce `FilesMatch` **et** à la liste `exclude` de `.github/workflows/deploy.yml`.
- Messages de commit en français, préfixés par la zone (ex. « Partenaires: … », « Coaching : … »).

## Git et déploiement

- Travail courant sur `develop`. Un push sur `main` déploie automatiquement en production (OVH, via `.github/workflows/deploy.yml`) : ne fusionner dans `main` ou pousser dessus que sur demande explicite.
