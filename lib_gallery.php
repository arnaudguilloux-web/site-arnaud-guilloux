<?php
declare(strict_types=1);

/* Galerie : photos affichées dans « Temps forts » (10 premières) et sur la page galerie (toutes).
   Ajouter une photo = déposer le .jpg dans assets/img/ et ajouter une ligne ci-dessous.
   Format : [fichier sans .jpg, titre FR, légende FR, titre EN, légende EN]. */
function gallery_items(): array
{
    return [
        ['roth-arrivee',      'Challenge Roth 2026.',          '7h46\'30 sur la distance IRONMAN.', 'Challenge Roth 2026.',         '7:46:30 over the IRONMAN distance.'],
        ['brasil-podium',     'IRONMAN Brasil 2026.',          'Troisième en 7h38.',                'IRONMAN Brasil 2026.',         'Third place in 7:38.'],
        ['kona-lave',         'Kona, Hawaï.',                  'Sur les champs de lave.',           'Kona, Hawaii.',                'On the lava fields.'],
        ['kona-check-in',     'Championnat du monde IRONMAN.', 'Au dépôt des vélos.',               'IRONMAN World Championship.',  'At bike check-in.'],
        ['roth-velo',         '180 km à vélo.',                'Là où se construit la course.',     '180 km on the bike.',          'Where the race is built.'],
        ['roth-course',       'Puis un marathon.',             'Jusqu\'à la ligne.',                'Then a marathon.',             'All the way to the line.'],
        ['roth-solarer-berg', 'Challenge Roth.',               'Entre deux haies de spectateurs.',  'Challenge Roth.',              'Between two walls of spectators.'],
        ['petit-minou',       'Bretagne.',                     'Au phare du Petit Minou.',          'Brittany.',                    'At the Petit Minou lighthouse.'],
    ];
}
