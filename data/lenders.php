<?php

declare(strict_types=1);

/**
 * Geldverstrekkers voor het tarievenblok.
 *
 * LET OP: dit zijn voorbeeldtarieven uit het ontwerp, geen echte marktrentes.
 * 'delta' is het verschil in procentpunten ten opzichte van de rente die de
 * calculator zelf hanteert; de getoonde rente wordt daaruit afgeleid.
 *
 * Dit bestand is bewust los van de templates, zodat er later een beheerscherm
 * op kan: rijen toevoegen, uitzetten met 'active' => false, of de volgorde
 * wijzigen, zonder aan de views te komen.
 */

return [
    [
        'name'   => 'Munt Hypotheken',
        'delta'  => -0.14,
        'active' => true,
        'note'   => ['nl' => 'tot 67% marktwaarde', 'en' => 'up to 67% of market value'],
    ],
    [
        'name'   => 'Tulp Hypotheken',
        'delta'  => -0.06,
        'active' => true,
        'note'   => ['nl' => 'tot 90% marktwaarde', 'en' => 'up to 90% of market value'],
    ],
    [
        'name'   => 'ASR',
        'delta'  => 0.02,
        'active' => true,
        'note'   => ['nl' => 'geen bereidstellingsprovisie', 'en' => 'no standby commission'],
    ],
    [
        'name'   => 'Rabobank',
        'delta'  => 0.09,
        'active' => true,
        'note'   => ['nl' => 'met duurzaamheidskorting', 'en' => 'with sustainability discount'],
    ],
    [
        'name'   => 'ING',
        'delta'  => 0.15,
        'active' => true,
        'note'   => ['nl' => 'boetevrij 20% aflossen', 'en' => '20% penalty-free repayment'],
    ],
];
