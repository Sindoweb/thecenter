<?php

return [
    'title' => 'The Center',
    'subtitle' => 'Your Destination for Work, Meetings, and Stays',
    'description' => 'The Center brings together a versatile co-working space, professional conference rooms, and comfortable B&B accommodation all in one inspiring location.',

    'coworking' => [
        'title' => 'Co-Working Space',
        'description' => 'A productive and collaborative environment with flexible options from daily passes to unlimited monthly subscriptions.',
        'features' => [
            'Base capacity for 6 people',
            'Expandable to 26 people when adjacent rooms are available',
            'High-speed internet',
            'Coffee and tea facilities',
        ],
        'pricing' => [
            'day_pass' => 'Day Pass: €35',
            'monthly_1_day' => '1 Day/Week: €120/month',
            'monthly_3_days' => '3 Days/Week: €300/month',
            'unlimited' => 'Unlimited: €450/month',
        ],
    ],

    'conference' => [
        'title' => 'Conference Rooms',
        'description' => 'Professional meeting spaces perfect for presentations, workshops, and important discussions.',
        'rooms' => [
            'glow' => [
                'name' => 'The Glow',
                'capacity' => 'Up to 8 people',
                'pricing' => 'Half-day: €140 | Full-day: €250',
            ],
            'ray' => [
                'name' => 'The Ray',
                'capacity' => 'Up to 12 people',
                'pricing' => 'Half-day: €160 | Full-day: €300',
            ],
            'universe' => [
                'name' => 'The Universe',
                'capacity' => 'Up to 20 people (combined space)',
                'pricing' => 'Half-day: €400 | Full-day: €700',
            ],
        ],
        'quarterly_subscription' => 'Quarterly Subscription: Reserve 4 half or full days per quarter with 20% discount',
    ],

    'accommodation' => [
        'title' => 'B&B Accommodation',
        'description' => 'Comfortable overnight stays in our beautifully appointed rooms.',
        'rooms' => [
            'single' => 'Single Room (The Sun or The Moon): €110/night',
            'both' => 'Both Rooms (up to 4 people): €200/night',
        ],
        'weekend_package' => 'Weekend Package (Fri-Sun): Accommodation + optional light therapy sessions',
    ],

    'cta' => 'Book Now',
];
