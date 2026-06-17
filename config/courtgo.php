<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bookable sports
    |--------------------------------------------------------------------------
    |
    | The curated list of sports owners can choose from (modelled on
    | courtsite.my's categories). Owners pick "Other" for anything not here.
    | Customers also filter by this list, so categories stay consistent.
    |
    */

    'sports' => [
        'Badminton',
        'Futsal',
        'Football',
        'Tennis',
        'Pickleball',
        'Padel',
        'Basketball',
        'Volleyball',
        'Squash',
        'Netball',
        'Field Hockey',
        'Table Tennis',
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

];
