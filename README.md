# waktu
```text
rimba/waktu
├── config/
│   └── bites.php
├── resources/
│   └── views/
│       └── filament/
│           ├── admin/
│           │   └── pages/
│           │       └── manage-json-calendar.blade.php
│           └── staff/
│               └── pages/
│                   └── calendar.blade.php
├── src/
│   ├── TimeServiceProvider.php
│   ├── Enums/
│   │   └── EventType.php
│   ├── Services/
│   │   ├── TimeJsonRepository.php
│   │   ├── CalendarConverterService.php
│   │   └── CalendarEventService.php
│   └── Http/
│       └── UI/
│           ├── Admin/
│           │   └── Pages/
│           │       ├── ManageJsonCalendar.php
│           │       ├── ManageHolidays.php
│           │       └── ManageWorkdays.php
│           └── Staff/
│               └── Pages/
│                   └── Calendar.php
└── storage/
    └── app/
        └── public/
            └── time/
                ├── holidays.json
                └── workdays.json
```

# Rimba Waktu JSON Calendar, Filament v5

JSON is the source of truth. Admins CRUD actual dated holidays and workdays, import JSON/ICS, and export JSON/ICS. Staff see the merged calendar.

## Install
1. Copy `src`, `resources`, and `config` into `rimba/waktu`.
2. Copy the example JSON files to `storage/app/public/time`.
3. In `TimeServiceProvider`, load the package configuration and views:
```php
protected string $configFile = __DIR__.'/../config/bites.php';
protected string $viewsPath = __DIR__.'/../resources/views';
```
4. Run `php artisan storage:link` and ensure the `public` disk is writable.
5. Ensure FullCalendar's `index.global.min.js` exists at `public/js/index.global.min.js`.
6. Discover/register Admin pages `ManageHolidays`, `ManageWorkdays`, and Staff page `Calendar` in their panels.

## Remove from panel discovery
Remove the Eloquent `EventResource` and `ShiftResource`. The old database tables may remain temporarily, but this UI does not use them.

## Import strategies
- update: upsert by UID
- append: ignore duplicate UIDs
- replace_range: remove existing records inside the imported date range, then import
- replace_all: replace the file

## ICS scope
The importer supports standard VEVENT UID, SUMMARY, DESCRIPTION, DTSTART, DTEND, STATUS, CATEGORIES, TZID/VALUE parameters and Rimba extension fields. It intentionally stores resolved dated events. RRULE expansion, EXDATE, RDATE, VTIMEZONE generation, and RECURRENCE-ID are not part of this minimal version.

## Production notes
- Restrict Admin pages through policies or `canAccess()`.
- Back up JSON files before business-critical monthly imports.
- Validate imported schedules in the form before saving.
- Keep stable UIDs so future imports update the correct records.
