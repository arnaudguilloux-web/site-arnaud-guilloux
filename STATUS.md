# Statut du site

Avancement et points ouverts avant la mise en ligne. Source : le document « Site d'Arnaud : points à vérifier » (21 nov. 2026), où Arnaud répond ligne par ligne. Cocher ici au fur et à mesure ; mettre à jour la date quand le document change.

Dernière mise à jour : 2026-11-23

## Bloquants avant la mise en ligne

- [ ] **Réglages** (back office) : e-mail de réception du formulaire (vide), SIRET (vide), LinkedIn (vide, facultatif). Confirmer Instagram, Strava, Facebook et X.
- [ ] **Calendrier** : aucune course à venir saisie, donc la section et son lien de menu sont masqués. Saisir les prochaines courses.
- [ ] **Partenaires** : liens et descriptions d'EnoWatt et AquaSelf ; confirmer que le site du club est nissa-triathlon-team.org.
- [ ] **Offres coaching** : valider libellés et tarifs ; compléter « Séminaires » et « Rendez-vous 1-1 · Consulting » (aujourd'hui « Sur demande »). Si le back office a déjà une liste enregistrée, y ajouter ces deux cartes à la main.
- [ ] **Témoignages** : accord des 7 athlètes confirmé pour la publication ; ville de Guillaume à préciser ou laisser vide ; relire la citation de Phil avec lui.
- [ ] **Photos** : crédits et droits des 8 photos du site, et des 2 nouvelles photos du hero (course à pied, vélodrome).
- [ ] **Texte d'accueil** : relecture par Arnaud, mention du Nissa Triathlon comprise.
- [ ] **Rendu mobile et iPad** : toutes les pages, en FR et en EN.

## Palmarès et chiffres à confirmer par Arnaud

PTO ne donne ni lieux ni distances et contient quelques erreurs. Le palmarès affiche 59 courses (55 de PTO, 4 saisies à la main).

- [ ] IRONMAN Brasil 2026 : 3e, 7h38'00 (PTO). Temps exact ? Le « 00 » laisse penser à un arrondi.
- [ ] Challenge Roth 2026 : 12e, 7h46'30, vélo 4h15'00, marathon 2h37'29. La tuile « Marathon après 4h15 de vélo » en dépend.
- [ ] IRONMAN South Africa 2023 : 7e, 7h35'08, mais PTO affiche une natation de 9'59 (fausse). Temps final fiable ? Sinon masquer la ligne.
- [ ] Courses absentes de PTO (place, temps, date) : Natureman 2017 (1er, 4h13'06) ; Alpe d'Huez L 2015 (1er, « Premier vainqueur français ») ; Championnats de France longue distance (2016 Baudreix 3e, 2015 Gravelines 2e).
- [ ] Mondial de St George : libellé « édition 2021 », couru le 7 mai 2022, 22e. Libellé correct ?
- [ ] Abandons (DNF) : Texas 2026, Kona 2024, 70.3 Campeche 2024, Cozumel 2023, TradeInn Full 2021, 70.3 Barcelone 2019, IRONMAN UK 2018, 70.3 Pays d'Aix 2017. Bien des abandons et non des non-partants ?
- [ ] Autres courses à ajouter (avant 2016 ou hors PTO) ? Saisie à la main dans le back office.
- [ ] Six chiffres de l'accueil (validés par Mart le 20 nov.) : 7h38 ; 5 championnats du monde ; 4 victoires (dont Wales 2019, « record de l'épreuve » à confirmer ; PTO n'en liste que 2) ; 4 podiums IRONMAN ; Top 100 PTO 2020–2024 (rangs 78, 51, 81, 42, 53) ; marathon 2h37.
- [ ] **Lieux** : 19 lieux remplis de mémoire à relire (onglet Palmarès), 2 vides : IRONMAN 70.3 Loire Valley 2025 et TradeInn Full 2021.

## Au lancement

- [ ] Arnaud crée son mot de passe sur `/admin/` juste après le premier déploiement (avant, le premier visiteur peut le choisir).
- [ ] Décocher « Site masqué des moteurs de recherche » dans Réglages le jour de l'ouverture, sinon pas d'indexation.
- [ ] Tester l'import PTO une fois en ligne ; en cas de blocage par l'hébergeur, un message s'affiche et rien n'est modifié.
- [ ] Décommenter la redirection HTTPS dans `.htaccess` une fois le certificat SSL actif.
- [ ] Secrets GitHub `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, puis fusion de `develop` dans `main`.

## Idées et à voir avec Arnaud

- [ ] Partenariat : revoir le contenu pour mieux expliquer l'intérêt de devenir partenaire.
- [ ] Coaching : revoir le contenu pour mieux décrire l'offre.
- [ ] Codes et affiliation : quels codes ou liens affiliés, avec quels partenaires et quel accord ; mention de transparence (commission) ; base légale à vérifier. Option retenue : codes dans la section Partenaires, éditables dans le back office.
- [ ] Photos du hero (3 photos écrites en dur dans `index.php`) : les rendre modifiables dans le back office. Pas urgent.
