# waktu
```text
rimba/waktu
├── config/
│   └── bites.php
├── resources/views/filament/
│   ├── admin/pages/manage-json-collection.blade.php
│   └── staff/pages/calendar.blade.php
├── src/
│   ├── Http/UI/Admin/Pages/
│   │   ├── ManageJsonCollection.php
│   │   ├── ManageHolidays.php
│   │   ├── ManageShiftDefinitions.php
│   │   └── ManageOverrides.php
│   ├── Http/UI/Staff/Pages/
│   │   └── Calendar.php
│   ├── Services/
│   │   ├── TimeJsonRepository.php
│   │   ├── CalendarConverterService.php
│   │   ├── ShiftGeneratorService.php
│   │   └── CalendarEventService.php
│   └── TimeServiceProvider.php
├── storage/app/public/time/
│   ├── holidays.json
│   ├── shift-definitions.json
│   └── overrides.json
└── README.md
```

## Source of truth
- `holidays.json`: company/public holidays
- `shift-definitions.json`: shift definition selected by a Spatie role such as `shift_code.X-4G3S`
- `overrides.json`: date-specific exceptions for a role

## Install
1. Copy package files into `rimba/waktu`.
2. Copy the three JSON files to `storage/app/public/time`.
3. Ensure `TimeServiceProvider` is registered.
4. Run `php artisan storage:link` and make the public disk writable.
5. Put FullCalendar global build at `public/js/index.global.min.js`.
6. Register/discover the three Admin pages and Staff Calendar page in their corresponding panels.
7. Remove old EventResource and ShiftResource registrations.

## Roles
The authenticated user must expose `getRoleNames()` and have at most one `shift_code.*` role. Examples:
- `shift_code.1-Normal`
- `shift_code.X-4G3S`
- `shift_code.R-6G4S`

## Important sample assumption
The X/Y/Z 4G3S examples use the anchor, offsets, and 24-day sequence from the earlier Waktu configuration. The R/T 6G4S sequence and times were not supplied, so those two JSON entries are explicitly marked `attributes.sample_assumption=true`. Replace them with the approved HR cycle, anchor, offset, and times before production use.

## Precedence
1. Matching date override
2. Holiday observation
3. Shift definition

## ICS
ICS import/export is intentionally limited to holidays. Shift definitions and overrides use JSON because they contain Rimba-specific role and cycle metadata.

## Security
Add your package permission checks through `canAccess()` on Admin pages. The package writes atomically but production deployments should also apply filesystem backups and restrict write access.
