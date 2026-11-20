# Site Arnaud Guilloux

Site vitrine bilingue (FR `/`, EN `/en/`) en PHP sans dépendance, pour hébergement mutualisé OVH.

## Structure

| Fichier | Rôle |
| --- | --- |
| `index.php` | Page publique (FR/EN), rendue côté serveur à partir du contenu |
| `assets/style.css` | Styles (thème clair, encre bleu nuit + accent marine, Inter). Couleurs, rayons et espacements se règlent dans `:root` |
| `contact.php` | Envoi du formulaire de contact par e-mail |
| `admin/` | Back office mobile (styles propres, en ligne dans `admin/index.php`) |
| `data/content.default.json` | Contenu de départ, versionné |
| `data/content.json` | Contenu en ligne, écrit par le back office (non versionné) |
| `.github/workflows/deploy.yml` | Déploiement automatique vers OVH à chaque push sur `main` |

## Mise en ligne

1. Créer un dépôt GitHub et y pousser ce dossier (branche `main`).
2. Dans le dépôt : *Settings → Secrets and variables → Actions*, créer `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (identifiants FTP du manager OVH).
3. Le push déclenche le déploiement dans `www/`. PHP 7.4 minimum (8.x recommandé, réglable dans le manager OVH).
4. **Tout de suite après le premier déploiement**, ouvrir `https://votre-domaine/admin/` et créer le mot de passe du back office : tant que ce n'est pas fait, le premier visiteur de cette page peut le choisir.
5. Dans *Réglages* : saisir l'e-mail de contact et les réseaux, puis masquer le bandeau « contenu d'exemple » une fois palmarès, tarifs et calendrier vérifiés (le site est en `noindex` tant que le bandeau est affiché).
6. Une fois le certificat SSL actif, décommenter la redirection HTTPS dans `.htaccess`.

## Back office

`/admin/` — rubriques : Calendrier, Palmarès, Mondiaux, Prestations, Partenaires, Chiffres, Accroche, Réglages.

- Le contenu saisi vit dans `data/content.json` sur le serveur ; les déploiements ne l'écrasent pas. La version précédente est gardée dans `data/content.bak.json`.
- Mot de passe oublié : supprimer `data/admin.php` par FTP, puis rouvrir `/admin/`.
- Après 5 échecs de connexion, l'accès est bloqué 15 minutes.

Les textes fixes de l'interface (titres de sections, argumentaire sponsors, libellés du formulaire) sont dans le tableau `$T` de `index.php`.

## Aperçu local

Avec PHP installé :

```bash
php -S localhost:8080
```
