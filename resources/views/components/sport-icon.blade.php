@props(['sport' => ''])

{{-- A unique, hand-drawn line icon for each sport shown on the homepage tiles.
     Outline style (currentColor) so it inherits the tile's text colour and works
     in light + dark. Unknown / custom sports fall back to a generic marker. --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
     {{ $attributes->merge(['class' => 'size-5']) }}>
    @switch($sport)

        {{-- Racquet --}}
        @case('Pickleball')
            <rect x="3.5" y="2.5" width="9" height="12" rx="3.8"/>
            <path d="M8 14.5V20"/>
            <circle cx="17.5" cy="16" r="3.2"/>
            <circle cx="16.4" cy="15.3" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="18.5" cy="15.3" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="17.5" cy="17.2" r=".5" fill="currentColor" stroke="none"/>
            @break
        @case('Badminton')
            <path d="M9.6 13.8a2.4 2.4 0 0 0 4.8 0"/>
            <path d="M6 4l3.6 9.8M18 4l-3.6 9.8M12 3.4V13.8M9.6 3.8l1 10M14.4 3.8l-1 10"/>
            <path d="M6 4q6-2 12 0"/>
            @break
        @case('Padel')
            <path d="M12 14.2V20.5"/>
            <circle cx="12" cy="8" r="6"/>
            <circle cx="9.6" cy="7" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="14.4" cy="7" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="12" cy="5.4" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="12" cy="9.6" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="10.6" cy="8.4" r=".5" fill="currentColor" stroke="none"/>
            <circle cx="13.4" cy="8.4" r=".5" fill="currentColor" stroke="none"/>
            @break
        @case('Squash')
            <ellipse cx="9" cy="8" rx="4.2" ry="5.2"/>
            <path d="M6.5 8h5M9 4v8"/>
            <path d="M9 13.2 14 20"/>
            <circle cx="18.5" cy="9" r="1.5"/>
            @break
        @case('Tennis')
            <circle cx="9" cy="8" r="5.2"/>
            <path d="M9 3v10M4 8h10"/>
            <path d="M9 13.2 13 19"/>
            <circle cx="18" cy="14" r="2.4"/>
            <path d="M16 12.8a2.4 2.4 0 0 0 0 2.6"/>
            @break
        @case('Table Tennis')
            <circle cx="9.5" cy="8" r="5"/>
            <path d="M9.5 13 12.5 19"/>
            <circle cx="18" cy="13" r="1.6"/>
            @break
        @case('Skyball')
            <circle cx="6.5" cy="16.5" r="2.4"/>
            <path d="M8 14.4C13 4 16 4 20 8" stroke-dasharray="2 2"/>
            <path d="M20 8l.4-2.6M20 8l-2.6.4"/>
            @break

        {{-- Team --}}
        @case('Futsal')
            <rect x="3" y="6" width="11" height="10" rx="1"/>
            <path d="M3 9.3h11M3 12.6h11M6.7 6v10M10.3 6v10" stroke-opacity=".45"/>
            <circle cx="18" cy="16.5" r="2.6"/>
            @break
        @case('Football')
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 7.5l3 2.2-1.1 3.5h-3.8L9 9.7z"/>
            <path d="M12 4v3.5M15 9.7l3-1.2M13.9 13.2l2.2 2.8M10.1 13.2l-2.2 2.8M9 9.7L6 8.5"/>
            @break
        @case('Light Volleyball')
            <circle cx="11" cy="12.5" r="6.5"/>
            <path d="M11 6c-2.2 3-2.2 10 0 13M4.8 10.5c3 1.6 11 1.6 12.4-1M5 17c2.6-2.2 9-3 12-1" stroke-opacity=".85"/>
            <path d="M19 3.5v3M17.5 5h3"/>
            @break
        @case('Volleyball')
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 4c-2.5 3.5-2.5 12.5 0 16M4.5 9.5c3.5 2 12.5 2 15-.5M5 17c3-2.5 10-3.5 14-1"/>
            @break
        @case('3x3 Basketball')
            <rect x="3.5" y="3" width="11" height="8" rx="1"/>
            <rect x="6.5" y="6.5" width="5" height="3"/>
            <path d="M6 11h7l-1 3.5H7z"/>
            <circle cx="18" cy="17" r="2.6"/>
            @break
        @case('Basketball')
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 4v16M4 12h16"/>
            <path d="M6.3 6.3C9 9 9 15 6.3 17.7M17.7 6.3C15 9 15 15 17.7 17.7"/>
            @break
        @case('Netball')
            <path d="M11 21V5"/>
            <ellipse cx="14" cy="5" rx="3" ry="1.3"/>
            <path d="M11.6 6l1 3M16.4 6l-1 3M14 6.3v3.2" stroke-opacity=".55"/>
            @break
        @case('Field Hockey')
            <path d="M9 3v11c0 3 2 4 4 4h3"/>
            <circle cx="18.5" cy="18" r="1.5"/>
            @break
        @case('Dodgeball')
            <circle cx="13.5" cy="12" r="6"/>
            <path d="M3.5 8h3M2.5 12h3.5M3.5 16h3"/>
            @break
        @case('Lawn Bowl')
            <circle cx="10" cy="13" r="5"/>
            <circle cx="10" cy="13" r="1" fill="currentColor" stroke="none"/>
            <circle cx="18.5" cy="9.5" r="1.4"/>
            <path d="M2 19h20" stroke-opacity=".4"/>
            @break
        @case('Frisbee')
            <ellipse cx="12" cy="13.5" rx="9" ry="3"/>
            <path d="M5 12.7c1.6-1.6 12.4-1.6 14 0"/>
            @break
        @case('Cricket')
            <path d="M8 5v13M11 5v13M14 5v13"/>
            <path d="M7 5h8"/>
            <circle cx="18.5" cy="16" r="1.8"/>
            <path d="M17.1 15a1.8 1.8 0 0 1 2.8 0" stroke-opacity=".5"/>
            @break
        @case('Captain Ball')
            <circle cx="12" cy="8" r="3.2"/>
            <path d="M5 13a7 5 0 0 0 14 0"/>
            <path d="M8 18l-1 2M16 18l1 2M12 18.5v2"/>
            @break
        @case('Handball')
            <circle cx="14" cy="8" r="3"/>
            <path d="M5 19v-4M7.5 19v-5M10 19v-4.5M12.5 19v-4M4.5 15a4 4 0 0 0 8 .3"/>
            @break
        @case('Indoor Hockey')
            <path d="M5 5l9 12M5 5l1 3M5 5l3-1"/>
            <path d="M19 5L10 17"/>
            <circle cx="12" cy="20" r="1.3"/>
            @break
        @case('Sepak Takraw')
            <circle cx="12" cy="12" r="8"/>
            <path d="M4 12h16M12 4v16M6.3 6.3l11.4 11.4M17.7 6.3L6.3 17.7" stroke-opacity=".7"/>
            @break
        @case('Teqball')
            <path d="M3 16c4-5 14-5 18 0"/>
            <path d="M3 16v2.5M21 16v2.5M12 11.5V9"/>
            <circle cx="12" cy="6.5" r="2.2"/>
            @break
        @case('Flag Football')
            <ellipse cx="9" cy="14" rx="5.5" ry="3.2" transform="rotate(-35 9 14)"/>
            <path d="M7.5 15.5l3-3" stroke-opacity=".55"/>
            <path d="M17 4v9M17 4l4 1.5-4 1.5"/>
            @break
        @case('Rugby')
            <ellipse cx="12" cy="12" rx="4.5" ry="8" transform="rotate(40 12 12)"/>
            <path d="M9.5 12h5M11.2 10.6l1.6 2.8M11.2 13.4l1.6-2.8" stroke-opacity=".6"/>
            @break

        {{-- Water --}}
        @case('Free Diving')
            <path d="M8 4l3 1v8l-4 2c-2-4-1-9 1-11z"/>
            <circle cx="15.5" cy="7" r="1"/>
            <circle cx="17.5" cy="11" r="1.3"/>
            <circle cx="15.5" cy="14.5" r=".8"/>
            @break
        @case('Mermaiding')
            <path d="M11 3c-2 5-2 9 0 12"/>
            <path d="M11 15c-3 0-5 2-6 4 3 0 5-1 6-2 1 1 3 2 6 2-1-2-3-4-6-4z"/>
            @break
        @case('Scuba Diving')
            <rect x="3.5" y="7" width="12" height="8" rx="3.5"/>
            <path d="M9.5 15v2a2.5 2.5 0 0 0 5 0v-1"/>
            <circle cx="18.5" cy="6" r=".9"/>
            <circle cx="20.5" cy="9.5" r="1.1"/>
            @break
        @case('Swimming')
            <circle cx="7" cy="6.5" r="2"/>
            <path d="M3 13c2-2 4-2 6 0 2-3 5-5 9-3"/>
            <path d="M2 18c3-1.5 5-1.5 8 0s5 1.5 8 0M2 20.5c3-1.5 5-1.5 8 0s5 1.5 8 0" stroke-opacity=".5"/>
            @break

        {{-- Recreational --}}
        @case('Archery Tag')
            <path d="M7 3C13 8 13 16 7 21"/>
            <path d="M7 3L7 21" stroke-opacity=".5"/>
            <path d="M5 12h15M17 9l3 3-3 3"/>
            @break
        @case('Paintball')
            <path d="M12 6c1.6-1 3 .2 3 1.6s2 1 2.4 2.4-1 2.4 0 3.5-1 2.4-2.5 2-2 1.4-3.4 1-2.5-1-3.6 0-2.4-1-2-2.4-.2-2 1-3.5 2-2.4 1.6.4 2.7-.6z"/>
            <circle cx="19" cy="6" r=".7" fill="currentColor" stroke="none"/>
            <circle cx="6" cy="18" r=".6" fill="currentColor" stroke="none"/>
            @break
        @case('Zorb Attack')
            <circle cx="12" cy="12" r="9"/>
            <circle cx="12" cy="11" r="1.7"/>
            <path d="M12 12.7v3.5M10 14l4 0M10 18.5l2-2.3 2 2.3"/>
            @break
        @case('Bowling')
            <path d="M9 3.2c-1.8 1.8-1.8 5.5 0 7.3 0 4-1 8-2 9.5h4c-1-1.5-2-5.5-2-9.5 1.8-1.8 1.8-5.5 0-7.3a2.5 2.5 0 0 0 0 0z"/>
            <circle cx="18" cy="17" r="3"/>
            <circle cx="17" cy="16" r=".4" fill="currentColor" stroke="none"/>
            <circle cx="18.4" cy="16" r=".4" fill="currentColor" stroke="none"/>
            <circle cx="17.7" cy="17.3" r=".4" fill="currentColor" stroke="none"/>
            @break
        @case('Foosball')
            <path d="M3 6h18"/>
            <path d="M12 6v4"/>
            <circle cx="12" cy="11.2" r="1.4"/>
            <path d="M10 18l2-5.4 2 5.4"/>
            <circle cx="17.5" cy="19" r="1.3"/>
            @break
        @case('Golf Driving Range')
            <path d="M7 4v16"/>
            <path d="M7 4l7 2-7 2"/>
            <circle cx="16" cy="18" r="1.5"/>
            <path d="M16 19.5v1.5M14.8 21h2.4"/>
            @break
        @case('Go-Kart')
            <path d="M3 14h4l2-3h5l2 3h4"/>
            <path d="M9 11l1-2h3l1 2"/>
            <circle cx="7" cy="17" r="2"/>
            <circle cx="17" cy="17" r="2"/>
            @break
        @case('Martial Arts')
            <path d="M3 10h18v3H3z"/>
            <path d="M9 13l-2 7 3-2 2 2-1-7M15 13l2 7-3-2"/>
            @break
        @case('Pool Table')
            <rect x="3" y="6" width="18" height="12" rx="2"/>
            <circle cx="4.6" cy="7.6" r=".8"/>
            <circle cx="19.4" cy="7.6" r=".8"/>
            <circle cx="4.6" cy="16.4" r=".8"/>
            <circle cx="19.4" cy="16.4" r=".8"/>
            <circle cx="10" cy="12" r="1.3"/>
            <circle cx="13" cy="12" r="1.3"/>
            @break

        {{-- Fitness & spaces --}}
        @case('Dance Studio')
            <circle cx="13" cy="4.5" r="1.8"/>
            <path d="M13 6.3l-2 6 3 1M13 6.3c2 1 4 0 5-1M11 12.3l-3 5M14 13.3l2 6"/>
            @break
        @case('Fitness Space')
            <circle cx="12" cy="5" r="1.8"/>
            <path d="M12 6.8v6M12 12.8l-4 5M12 12.8l4 5M8 9h8"/>
            @break
        @case('Gym')
            <path d="M4 12h16"/>
            <path d="M6 8v8M8 9.5v5"/>
            <path d="M18 8v8M16 9.5v5"/>
            @break
        @case('Running Track')
            <rect x="3" y="7" width="18" height="10" rx="5"/>
            <rect x="6.5" y="9.5" width="11" height="5" rx="2.5"/>
            @break
        @case('Wall Climbing')
            <path d="M5 3v18"/>
            <circle cx="9" cy="6" r="1" fill="currentColor" stroke="none"/>
            <circle cx="13" cy="10" r="1" fill="currentColor" stroke="none"/>
            <circle cx="10" cy="14" r="1" fill="currentColor" stroke="none"/>
            <circle cx="15" cy="16.5" r="1" fill="currentColor" stroke="none"/>
            <path d="M9 6l3.5 4-2 4 4.5 2.5" stroke-opacity=".55"/>
            @break
        @case('Event Space')
            <path d="M3 9l9-5 9 5"/>
            <path d="M3 9c1.5 1.5 3 1.5 4.5 0s3-1.5 4.5 0 3 1.5 4.5 0 3-1.5 4.5 0"/>
            <path d="M5 9.5V20h14V9.5"/>
            @break
        @case('Sporty Celebration')
            <path d="M4 20l5-12 7 7z"/>
            <path d="M16 4l1 1M19.5 3l-.6 1.6M20.5 7.5l-1.6.4M14.5 6l1-1"/>
            <circle cx="18.5" cy="9.5" r=".5" fill="currentColor" stroke="none"/>
            @break
        @case('Event Room')
            <rect x="4" y="4" width="16" height="11" rx="1"/>
            <path d="M12 15v3M8 21l1-3M16 21l-1-3M8 21h8"/>
            @break
        @case('Chalet')
            <path d="M4 11l8-6 8 6"/>
            <path d="M6 10v9h12v-9"/>
            <path d="M10 19v-4h4v4"/>
            @break

        {{-- Classes --}}
        @case('Boxing')
            <path d="M7 8a4 4 0 0 1 8 0v2h1a2 2 0 0 1 0 4h-1v1a3 3 0 0 1-3 3H10a3 3 0 0 1-3-3z"/>
            <path d="M7 11h6"/>
            @break
        @case('Brazilian Jiu-Jitsu')
            <path d="M6 4l6 4 6-4"/>
            <path d="M6 4v14M18 4v14"/>
            <path d="M5 12h14"/>
            <path d="M10 12l-1 4 3-1 3 1-1-4"/>
            @break
        @case('Capoeira')
            <circle cx="12" cy="19" r="1.6"/>
            <path d="M12 17.4V11M12 11l-4-5M12 11l4-5M8 6L6 4M16 6l2-2"/>
            @break
        @case('Fitness')
            <path d="M12 20s-7-4.5-7-9a3.5 3.5 0 0 1 7-1 3.5 3.5 0 0 1 7 1c0 1-.3 2-.9 3"/>
            <path d="M10.5 12h2l1 2 2-3.5 1 1.5h2.5"/>
            @break
        @case("Fighter's Strength And Conditioning")
            <path d="M9.5 8a2.5 2.5 0 0 1 5 0"/>
            <path d="M8 9h8c1.5 1.5 2 3.5 2 5a6 6 0 0 1-12 0c0-1.5.5-3.5 2-5z"/>
            @break
        @case('Grappling')
            <circle cx="9" cy="12" r="5"/>
            <circle cx="15" cy="12" r="5"/>
            @break
        @case('Kickboxing')
            <circle cx="8" cy="5" r="1.6"/>
            <path d="M8 6.6v5M8 11.6l-1 6M8 11.6l9-2M8 8.5l-3 1.5"/>
            @break
        @case('MMA')
            <path d="M8 3h8l5 5v8l-5 5H8l-5-5V8z"/>
            <path d="M3 8h18M3 16h18M8 3v18M16 3v18" stroke-opacity=".3"/>
            @break
        @case('Muay Thai')
            <path d="M5 12a7 5 0 0 1 14 0"/>
            <circle cx="12" cy="15" r="3.5"/>
            <path d="M5 12l-1.2 4.5M19 12l1.2 4.5"/>
            @break
        @case('Muay Thai Fitness')
            <rect x="6" y="4" width="9" height="13" rx="4"/>
            <path d="M15 9h2a2 2 0 0 1 0 4h-2"/>
            <path d="M10.5 8v5" stroke-opacity=".4"/>
            @break
        @case('Taekwondo')
            <rect x="4" y="6" width="16" height="4.3" rx=".6"/>
            <rect x="4" y="13" width="16" height="4.3" rx=".6"/>
            <path d="M10.5 6l2 4.3M13.5 13l-2 4.3" stroke-opacity=".6"/>
            @break

        @default
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 8v8M8 12h8"/>
    @endswitch
</svg>
