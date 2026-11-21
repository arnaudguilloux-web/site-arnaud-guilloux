<?php
declare(strict_types=1);

/* Galerie : photos affichées dans « Temps forts » (10 premières) et sur la page galerie (toutes).
   Ajouter une photo = déposer le .jpg dans assets/img/ et ajouter une ligne ci-dessous.
   Format : [fichier sans .jpg, titre FR, légende FR, titre EN, légende EN, type, cadrage facultatif]
   cadrage : object-position du carrousel (cadre 3:2) pour les photos verticales, ex. '50% 30%' (défaut : '50% 30%').
   type : 'athlete' (photos de course, carrousel de l'accueil) ou 'coach' (photos du coaching, carrousel de la page Coach).
   Sans type, la photo est « athlete ». gallery_items('coach') ne renvoie que les photos coach ; sans argument, tout. */
function gallery_items(?string $type = null): array
{
    $all = [
        ['roth-arrivee',      'Challenge Roth 2026.',          '7h46\'30 sur la distance IRONMAN.', 'Challenge Roth 2026.',         '7:46:30 over the IRONMAN distance.', 'athlete'],
        ['brasil-podium',     'IRONMAN Brasil 2026.',          'Troisième en 7h38.',                'IRONMAN Brasil 2026.',         'Third place in 7:38.', 'athlete'],
        ['kona-lave',         'Kona, Hawaï.',                  'Sur les champs de lave.',           'Kona, Hawaii.',                'On the lava fields.', 'athlete'],
        ['kona-check-in',     'Championnat du monde IRONMAN.', 'Au dépôt des vélos.',               'IRONMAN World Championship.',  'At bike check-in.', 'athlete'],
        ['roth-velo',         '180 km à vélo.',                'Là où se construit la course.',     '180 km on the bike.',          'Where the race is built.', 'athlete'],
        ['roth-course',       'Puis un marathon.',             'Jusqu\'à la ligne.',                'Then a marathon.',             'All the way to the line.', 'athlete'],
        ['roth-solarer-berg', 'Challenge Roth.',               'Entre deux haies de spectateurs.',  'Challenge Roth.',              'Between two walls of spectators.', 'athlete'],
        ['petit-minou',       'Bretagne.',                     'Au phare du Petit Minou.',          'Brittany.',                    'At the Petit Minou lighthouse.', 'athlete'],
        ['nice-reconnaissance', 'Nice.',                         'Stage de reconnaissance du parcours du Championnat du monde IRONMAN 70.3.', 'Nice.', 'Course recon camp for the IRONMAN 70.3 World Championship.', 'coach', '50% 55%'],
        ['nice-70-3-wc',        'Championnat du monde IRONMAN 70.3.', 'Nice, 2026.',                       'IRONMAN 70.3 World Championship.', 'Nice, 2026.', 'coach', '50% 30%'],
        ['kona-parc-velo',      'Kona, Hawaï.',                  'Au parc à vélo avec Geoffroy.',    'Kona, Hawaii.',                'At the bike park with Geoffroy.', 'coach', '50% 50%'],
        ['texas-athletes',      'IRONMAN Texas.',                'Avec une bonne partie des athlètes.', 'IRONMAN Texas.',            'With many of the athletes.', 'coach', '50% 30%'],
        ['kona-depose-velos',   'Championnat du monde IRONMAN.', 'Dépose des vélos, Hawaï.',        'IRONMAN World Championship.',  'Bike drop-off, Hawaii.', 'coach', '50% 40%'],
        ['lanzarote-stage',     'Stage à Lanzarote.', 'Fin de longue journée.',                   'Lanzarote camp.',              'End of a long day.', 'coach', '50% 40%'],
    ];
    if ($type === null) {
        return $all;
    }
    return array_values(array_filter($all, static function (array $g) use ($type): bool {
        return ($g[5] ?? 'athlete') === $type;
    }));
}
