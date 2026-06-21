<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bookable sports & activities
    |--------------------------------------------------------------------------
    |
    | The full curated list owners can choose from (modelled on
    | courtsite.my's category page), grouped only by comment. Owners pick
    | "Other" for anything not here. Customers filter by this same list.
    |
    */

    'sports' => [
        // Racquet
        'Pickleball', 'Badminton', 'Padel', 'Squash', 'Tennis', 'Table Tennis', 'Skyball',
        // Team
        'Futsal', 'Football', 'Light Volleyball', 'Volleyball', '3x3 Basketball', 'Basketball',
        'Netball', 'Field Hockey', 'Dodgeball', 'Lawn Bowl', 'Frisbee', 'Cricket', 'Captain Ball',
        'Handball', 'Indoor Hockey', 'Sepak Takraw', 'Teqball', 'Flag Football', 'Rugby',
        // Water
        'Free Diving', 'Mermaiding', 'Scuba Diving', 'Swimming',
        // Recreational
        'Archery Tag', 'Paintball', 'Zorb Attack', 'Bowling', 'Foosball', 'Golf Driving Range',
        'Go-Kart', 'Martial Arts', 'Pool Table',
        // Fitness & spaces
        'Dance Studio', 'Fitness Space', 'Gym', 'Running Track', 'Wall Climbing',
        'Event Space', 'Sporty Celebration', 'Event Room', 'Chalet',
        // Classes
        'Boxing', 'Brazilian Jiu-Jitsu', 'Capoeira', 'Fitness', "Fighter's Strength And Conditioning",
        'Grappling', 'Kickboxing', 'MMA', 'Muay Thai', 'Muay Thai Fitness', 'Taekwondo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Popular sports
    |--------------------------------------------------------------------------
    |
    | A short subset shown as quick tiles on the homepage (the full list is
    | searchable in the filters).
    |
    */

    'popular_sports' => [
        'Badminton', 'Futsal', 'Football', 'Tennis', 'Pickleball',
        'Padel', 'Basketball', 'Volleyball', 'Squash', 'Table Tennis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sport icons
    |--------------------------------------------------------------------------
    |
    | Each sport's tile icon is a hand-drawn SVG in the <x-sport-icon> Blade
    | component (resources/views/components/sport-icon.blade.php), keyed by the
    | sport name above. Add a matching @case there when you add a sport here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Weekdays
    |--------------------------------------------------------------------------
    |
    | Day-of-week options, keyed by Carbon's dayOfWeek (0=Sun … 6=Sat) but
    | ordered Monday-first for display, since owners don't think of Sunday as
    | "day 0". Owners tick the days a slot applies to.
    |
    */

    'weekdays' => [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slot lengths
    |--------------------------------------------------------------------------
    |
    | How long each bookable slot can be, in hours (string keys avoid PHP's
    | float-key casting). Shown as a dropdown when owners build a schedule.
    |
    */

    'slot_lengths' => [
        '0.5' => '30 minutes',
        '1' => '1 hour',
        '1.5' => '1.5 hours',
        '2' => '2 hours',
        '3' => '3 hours',
        '4' => '4 hours',
    ],

    /*
    |--------------------------------------------------------------------------
    | Malaysian states & federal territories
    |--------------------------------------------------------------------------
    */

    'states' => [
        'Johor',
        'Kedah',
        'Kelantan',
        'Melaka',
        'Negeri Sembilan',
        'Pahang',
        'Penang',
        'Perak',
        'Perlis',
        'Sabah',
        'Sarawak',
        'Selangor',
        'Terengganu',
        'Kuala Lumpur',
        'Labuan',
        'Putrajaya',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support contact
    |--------------------------------------------------------------------------
    |
    | Shown to owners who need a sport that isn't in the curated list.
    |
    */

    'support_email' => env('COURTGO_SUPPORT_EMAIL', 'support@courtgo.my'),

    /*
    |--------------------------------------------------------------------------
    | Venue amenities
    |--------------------------------------------------------------------------
    |
    | The fixed list owners tick from; customers see the ticked ones. Each is
    | a label + a Heroicon name (free Flux icon set).
    |
    */

    'amenities' => [
        'parking' => ['label' => 'Parking', 'icon' => 'truck'],
        'wifi' => ['label' => 'Free WiFi', 'icon' => 'wifi'],
        'showers' => ['label' => 'Showers', 'icon' => 'sparkles'],
        'changing_rooms' => ['label' => 'Changing rooms', 'icon' => 'user-group'],
        'lockers' => ['label' => 'Lockers', 'icon' => 'lock-closed'],
        'surau' => ['label' => 'Surau', 'icon' => 'moon'],
        'cafe' => ['label' => 'Café / drinks', 'icon' => 'cake'],
        'equipment_rental' => ['label' => 'Equipment rental', 'icon' => 'shopping-bag'],
        'air_conditioned' => ['label' => 'Air-conditioned', 'icon' => 'cloud'],
        'spectator_seating' => ['label' => 'Spectator seating', 'icon' => 'eye'],
        'toilets' => ['label' => 'Toilets', 'icon' => 'home'],
        'wheelchair' => ['label' => 'Wheelchair accessible', 'icon' => 'heart'],
        'cctv' => ['label' => 'CCTV', 'icon' => 'video-camera'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Venue verification checklist
    |--------------------------------------------------------------------------
    |
    | Documents an owner uploads and an admin ticks off before a venue can be
    | approved. Each key is also the document "type". Admin approval is gated
    | on every item here being verified. This is a product policy, not legal
    | advice — confirm Malaysian specifics with SSM, LHDN and the local council.
    |
    */
    'verification' => [
        'ssm' => [
            'label' => 'SSM business registration',
            'owner_hint' => 'Upload your SSM business profile or certificate — Form A/D for a sole-proprietorship or partnership, or the company profile + Section 17 (Certificate of Incorporation) for an Sdn Bhd.',
            'admin_hint' => 'Cross-check the registration number on ssm-einfo.my; confirm the business name matches the venue.',
        ],
        'right_to_occupy' => [
            'label' => 'Right to operate the premises',
            'owner_hint' => 'Land title or quit-rent (cukai tanah) bill if you own the premises, or a stamped tenancy agreement / owner authorization letter if you rent or manage it.',
            'admin_hint' => 'Confirm the applicant owns or is authorized to operate at this address.',
        ],
        'council_licence' => [
            'label' => 'Council premise licence (PBT)',
            'owner_hint' => 'Your local council (Majlis/PBT) business-premise or composite licence for the venue — or proof you have applied for it.',
            'admin_hint' => 'Check the licence covers this venue address and has not expired.',
        ],
        'address_proof' => [
            'label' => 'Venue address proof',
            'owner_hint' => 'A recent utility bill or assessment (cukai pintu) bill showing the venue address.',
            'admin_hint' => 'Confirm the address matches the listing and the court photos.',
        ],
    ],

];
