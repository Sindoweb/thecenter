<?php

return [
    'title' => 'The Center',
    'subtitle' => 'Jouw Bestemming voor Werk, Vergaderingen en Verblijf',
    'description' => 'The Center brengt een veelzijdige co-working ruimte, professionele conferentiezalen en comfortabele B&B accommodatie samen op één inspirerende locatie.',

    'coworking' => [
        'title' => 'Co-Working Ruimte',
        'description' => 'Een productieve en collaboratieve omgeving met flexibele opties van dagpassen tot onbeperkte maandabonnementen.',
        'features' => [
            'Basiscapaciteit voor 6 personen',
            'Uitbreidbaar tot 26 personen wanneer aangrenzende kamers beschikbaar zijn',
            'Snel internet',
            'Koffie en thee faciliteiten',
        ],
        'pricing' => [
            'day_pass' => 'Dagpas: €35',
            'monthly_1_day' => '1 Dag/Week: €120/maand',
            'monthly_3_days' => '3 Dagen/Week: €300/maand',
            'unlimited' => 'Onbeperkt: €450/maand',
        ],
    ],

    'conference' => [
        'title' => 'Conferentiezalen',
        'description' => 'Professionele vergaderruimtes perfect voor presentaties, workshops en belangrijke besprekingen.',
        'rooms' => [
            'glow' => [
                'name' => 'The Glow',
                'capacity' => 'Tot 8 personen',
                'pricing' => 'Halve dag: €140 | Hele dag: €250',
            ],
            'ray' => [
                'name' => 'The Ray',
                'capacity' => 'Tot 12 personen',
                'pricing' => 'Halve dag: €160 | Hele dag: €300',
            ],
            'universe' => [
                'name' => 'The Universe',
                'capacity' => 'Tot 20 personen (gecombineerde ruimte)',
                'pricing' => 'Halve dag: €400 | Hele dag: €700',
            ],
        ],
        'quarterly_subscription' => 'Kwartaalabonnement: Reserveer 4 halve of hele dagen per kwartaal met 20% korting',
    ],

    'accommodation' => [
        'title' => 'B&B Accommodatie',
        'description' => 'Comfortabel overnachten in onze prachtig ingerichte kamers.',
        'rooms' => [
            'single' => 'Eenpersoonskamer (The Sun of The Moon): €110/nacht',
            'both' => 'Beide kamers (tot 4 personen): €200/nacht',
        ],
        'weekend_package' => 'Weekendpakket (Vr-Zo): Accommodatie + optionele lichttherapie sessies',
    ],

    'cta' => 'Boek Nu',
];
