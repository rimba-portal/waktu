<?php

declare(strict_types=1);

return [
    'ui' => [
        'packages' => [
            'bit-es/calendar/src' => 'Rimba\Time',
        ],
    ],
    'calendar' => [

        'default' => env('SHIFT_PATTERN_DEFAULT', 'WXYZ'),

        'patterns' => [
            'Normal' => [
                'anchor_date' => env('SHIFT_ANCHOR_NORMAL', '2026-01-05'), // Monday
                'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),

                // 5 workdays, then 2 rest days
                'segments' => [
                    [
                        'len' => 5,
                        'code' => 'D',
                        'label' => '🐓',
                        'start' => '08:30',
                        'end' => '17:30',
                        'color' => '#22c55e',
                    ],
                    [
                        'len' => 2,
                        'code' => 'R', // Weekend rest (hidden)
                    ],
                ],

                'cycle_length' => 7,

                'teams' => [
                    '1' => [
                        'label' => 'Normal Hours',
                        'offset' => 0,
                        'color' => '#22c55e',
                    ],
                ],
            ],

            // === 24-day W/X/Y/Z pattern ===
            '4G3S' => [
                'anchor_date' => env('SHIFT_ANCHOR_WXYZ', '2026-01-15'),
                'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),

                'segments' => [
                    ['len' => 6, 'code' => 'M', 'label' => '🐓', 'start' => '07:00', 'end' => '15:00', 'color' => '#90D5FF'],
                    ['len' => 2, 'code' => 'R'], // Rest (hidden)
                    ['len' => 6, 'code' => 'N', 'label' => '🦉', 'start' => '23:00', 'end' => '07:00(+1)', 'color' => '#B87333'],
                    ['len' => 2, 'code' => 'R'], // Rest (hidden)
                    ['len' => 6, 'code' => 'A', 'label' => '🦋', 'start' => '15:00', 'end' => '23:00', 'color' => '#CAF1DE'],
                    ['len' => 2, 'code' => 'R'], // Rest (hidden)
                ],

                'cycle_length' => 24,

                'teams' => [
                    'W' => ['label' => 'Team W', 'offset' => 22, 'color' => '#6b7280'],
                    'X' => ['label' => 'Team X', 'offset' => 10, 'color' => '#ef4444'],
                    'Y' => ['label' => 'Team Y', 'offset' => 4,  'color' => '#10b981'],
                    'Z' => ['label' => 'Team Z', 'offset' => 16, 'color' => '#3b82f6'],
                ],
            ],

            // === 12-day A/B/C pattern ===
            '3G2S' => [
                'anchor_date' => env('SHIFT_ANCHOR_ABC', '2026-01-07'),
                'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),

                // 4N, 2R, 4M, 2R
                'segments' => [
                    ['len' => 4, 'code' => 'N', 'label' => '🦉',  'start' => '19:00', 'end' => '07:00(+1)', 'color' => '#B87333'],
                    ['len' => 2, 'code' => 'R'], // Rest (hidden)
                    ['len' => 4, 'code' => 'M', 'label' => '🐓', 'start' => '07:00', 'end' => '19:00',     'color' => '#90D5FF'],
                    ['len' => 2, 'code' => 'R'], // Rest (hidden)
                ],

                'cycle_length' => 12,

                'teams' => [
                    'A' => ['label' => 'Team A', 'offset' => 0,  'color' => '#0ea5e9'],
                    'B' => ['label' => 'Team B', 'offset' => 11, 'color' => '#f59e0b'],
                    'C' => ['label' => 'Team C', 'offset' => 7,  'color' => '#10b981'],
                ],
            ],
            '3G3S' => [
                'anchor_date' => '2026-03-01', // Sunday = Day 1 in the provided table
                'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),

                // Base segments: define the three timed shifts (morning/afternoon/night).
                // Days that should not show events (Rest, Off, Holiday) will be handled via overrides below.
                'segments' => [
                    ['len' => 1, 'code' => 'M', 'label' => '🐓',   'start' => '07:00',     'end' => '15:00',      'color' => '#90D5FF'],
                    ['len' => 1, 'code' => 'A', 'label' => '🦋', 'start' => '15:00',     'end' => '23:00',      'color' => '#CAF1DE'],
                    ['len' => 1, 'code' => 'N', 'label' => '🦉',     'start' => '23:00',     'end' => '07:00(+1)',  'color' => '#B87333'],
                ],
                // Cycle repeats M->A->N daily
                'cycle_length' => 3,

                // Teams D/E/F rotate through M/A/N by applying offsets.
                // Offsets chosen so that on 2026-03-02 (Mon) Morning=F, Afternoon=D, Night=E as in your table.
                'teams' => [
                    'D' => ['label' => 'Team D', 'offset' => 1, 'color' => '#0ea5e9'],
                    'E' => ['label' => 'Team E', 'offset' => 2, 'color' => '#f59e0b'],
                    'F' => ['label' => 'Team F', 'offset' => 0, 'color' => '#10b981'],
                ],

            ],
        ],
    ],
];
