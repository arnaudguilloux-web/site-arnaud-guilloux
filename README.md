# Site Arnaud Guilloux

Site bilingue (FR `/`, EN `/en/`) en PHP sans dépendance, pour hébergement mutualisé OVH. Deux univers : **L'Athlète** (accueil) et **Le Coach** (`coaching.php`).

## Pages

| Page | Contenu |
| --- | --- |
| `index.php` | Accueil athlète : accroche, chiffres, Highlights (carrousel de photos de course), palmarès, calendrier, partenaires, contact |
| `coaching.php` | Page coach : offres, Highlights (photos des stages et courses), 5 témoignages au hasard, contact |
| `temoignages.php` | Tous les témoignages |
| `galerie.php` | Toutes les photos, filtre Tout / Athlète / Coach, agrandissement en pop-in |
| `palmares.php` | Palmarès complet, filtrable par année, type (Mondiaux, IRONMAN, 70.3, Challenge) et continent (liens `?year=&kind=&continent=`) |
| `contact.php` | Page Contact, et envoi du formulaire de contact par e-mail |
| `admin/` | Back office mobile (styles propres, en ligne dans `admin/index.php`) |

## Structure

| Fichier | Rôle |
| --- | --- |
| `lib.php` | Chargement/sauvegarde du contenu, helpers (`e()`, pictos des chiffres et des offres) |
| `lib_view.php` | Blocs partagés : en-tête et menu, pied de page, formulaire de contact, témoignages, carrousel Highlights |
| `lib_gallery.php` | Photos de la galerie : tri, légendes, dates, traitement des envois (voir « Ajouter une photo ») |
| `lib_pto.php` | Import du palmarès depuis Pro Triathlete Organisation (bouton dans le back office) |
| `lib_social.php` | Liens et pictos des réseaux sociaux |
| `assets/style.css` | Styles (thème clair, encre bleu nuit + accent marine, Inter). Couleurs, rayons et espacements se règlent dans `:root` |
| `assets/nav.js` | Menu : sous-menus au survol (bureau) ou au toucher (mobile), lien actif au défilement |
| `assets/carousel.js` | Carrousels (Highlights, témoignages) |
| `assets/lightbox.js` | Pop-in d'agrandissement de la galerie |
| `assets/contact.js` | Envoi du formulaire sans rechargement |
| `assets/img/` | Photos ; logos partenaires dans `assets/img/partners/` |
| `data/content.default.json` | Contenu de départ, versionné |
| `data/content.json` | Contenu en ligne, écrit par le back office (non versionné) |
| `.github/workflows/deploy.yml` | Déploiement automatique vers OVH à chaque push sur `main` |

## Mise en ligne

1. Créer un dépôt GitHub et y pousser ce dossier. Le déploiement part de la branche `main` ; le travail courant se fait sur `develop` et n'est mis en ligne qu'une fois fusionné dans `main`.
2. Dans le dépôt : *Settings → Secrets and variables → Actions*, créer `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (identifiants FTP du manager OVH).
3. Le push sur `main` déclenche le déploiement dans `site-arnaud/` (dossier racine d'arnaudguilloux.fr ; l'hébergement est partagé avec aquaself.fr, qui occupe `www/`). PHP 7.4 minimum (8.x recommandé, réglable dans le manager OVH).
4. **Tout de suite après le premier déploiement**, ouvrir `https://votre-domaine/admin/` et créer le mot de passe du back office : tant que ce n'est pas fait, le premier visiteur de cette page peut le choisir.
5. Dans *Réglages* : saisir l'e-mail de contact et les réseaux, puis, au lancement, désactiver « Site masqué des moteurs de recherche » une fois palmarès, tarifs et calendrier vérifiés (le site est en `noindex` tant que cette case est cochée).
6. Une fois le certificat SSL actif, décommenter la redirection HTTPS dans `.htaccess`.

## Back office

`/admin/` — onglets, dans l'ordre du site :

- **L'Athlète** : Accroche, Chiffres, Palmarès, Partenaires, Calendrier
- **Le Coach** : Coaching, Offres coaching, Témoignages
- **Site** : Menu, Réglages

Points utiles :

- Le contenu saisi vit dans `data/content.json` sur le serveur ; les déploiements ne l'écrasent pas. La version précédente est gardée dans `data/content.bak.json`.
- Le contenu en ligne **prime** sur `content.default.json` : une rubrique déjà enregistrée dans le back office n'est pas mise à jour par un changement du fichier de départ.
- Offres coaching : un picto au choix par offre. Dans un prix, « · » provoque un retour à la ligne (ex. un prix par type de vélo).
- Témoignages : le prénom peut être un lien facultatif vers la fiche Triathlon Lab de l'athlète. Le carrousel de la page coach tire 5 témoignages au hasard parmi ceux qui ont à la fois un chiffre clé et un titre (complété par les autres s'il en manque) ; « Tous les Témoignages » mène à la liste complète.
- Mot de passe : stocké sous forme de hash dans `data/admin.php` (jamais versionné). Oublié : supprimer ce fichier par FTP, puis rouvrir `/admin/`.
- Après 5 échecs de connexion, l'accès est bloqué 15 minutes.
- Menu : les sous-menus « L'Athlète » et « Le Coach » sont dynamiques. Une entrée n'existe que si sa section est affichée (Highlights s'il y a des photos du type correspondant, Palmarès s'il y a des courses visibles, Calendrier s'il y a une course à venir, Offres, Témoignages…). Seule la petite phrase sous chaque entrée se règle dans l'onglet Menu (vide = titre seul).
- Calendrier : trois types d'entrées, **Course**, **Stage** (date de fin facultative pour plusieurs jours) et **Événement** (tests aéro…). La prochaine course (à défaut, la prochaine entrée) s'affiche en grande carte avec compte à rebours ; les autres entrées sont listées dessous. Une entrée disparaît le lendemain de sa dernière date ; sans entrée à venir, la section et son lien de menu disparaissent.
- Palmarès : le continent d'une course (filtre de la page Palmarès) est déduit de la course PTO ou du lieu ; le champ « Continent » d'une course permet de le forcer. Une course sans continent reconnu reste visible, mais n'apparaît dans aucun filtre continent.
- Galerie : onglet « Photos » du back office (voir « Ajouter une photo » ci-dessous).

Les textes fixes de l'interface (titres de sections, libellés du formulaire) sont dans le tableau `$T` en tête de chaque page (`index.php`, `coaching.php`, …).

## Ajouter une photo

Back office → onglet **Galerie › Photos** → « + Ajouter une photo ».

- **Photo** : JPEG, PNG ou WebP, envoyée telle quelle. Le serveur la remet à l'endroit, la réduit à 1800 px sur le grand côté, la ré-encode en JPEG (qualité 82) et efface ses métadonnées GPS/EXIF. Elle est rangée dans `assets/gallery/` (non versionné, non écrasé par un déploiement). Limite d'envoi : `upload_max_filesize` / `post_max_size` de l'hébergement, donc quelques photos à la fois.
- **Titre** et **description** en FR et EN (l'EN retombe sur le FR s'il est vide).
- **Type** : `Athlète` alimente le carrousel Highlights de l'accueil, `Coach` celui de la page Coach. La page Galerie montre tout.
- **Date** (facultative) : affichée sous le titre, sur la page Galerie, dans les carrousels et dans la pop-in. Les photos sont triées de la plus récente à la plus ancienne ; sans date, elles passent après les datées, dans l'ordre saisi.
- **Cadrage** : point de focus dans le carrousel (cadre 3:2) pour les photos verticales.
- L'œil masque une photo sans la supprimer. Supprimer une entrée efface aussi son fichier dans `assets/gallery/` (les photos historiques de `assets/img/` ne sont jamais effacées).
- Chaque carrousel affiche les 5 photos les plus récentes de son type. Le lien « Highlights » du menu n'apparaît que s'il y a au moins une photo du type correspondant.

## Aperçu local

Avec PHP installé :

```bash
php -S localhost:8080
```

Le back office local écrit dans `data/content.json` (ignoré par git).
