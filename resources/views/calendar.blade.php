<x-filament-panels::page>
    <x-filament::section
        divided
        collapsible
        collapsed
        x-on:collapse-locations-table.window="isCollapsed = true"
    >
        <x-slot name="heading">Events</x-slot>

        {{ $this->table }}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            {{ $shiftCode ? 'My schedule: '.$shiftCode : 'Company calendar' }}
        </x-slot>

        <div wire:ignore id="waktu-calendar"></div>
    </x-filament::section>

    @assets
        <script src="{{ asset('js/rrule.min.js') }}"></script>
        <script src="{{ asset('js/calendar.min.js') }}"></script>
        <script src="{{ asset('js/index.global.min.js') }}"></script>
    @endassets

    @script
        <script>
            let waktuCalendar;

            const bootWaktuCalendar = () => {
                const element = document.getElementById('waktu-calendar');

                if (! element || typeof FullCalendar === 'undefined') {
                    return;
                }

                if (waktuCalendar) {
                    waktuCalendar.destroy();
                }

                waktuCalendar = new FullCalendar.Calendar(element, {
                    initialView: 'dayGridMonth',
                    firstDay: 1,
                    weekNumbers: true,
                    height: 600,

                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'multiMonthYear,dayGridMonth,timeGridWeek',
                    },

                    events: @js($events),

                    eventDidMount: ({ event, el }) => {
                        const description = event.extendedProps.description;

                        el.title = description
                            ? `${event.title}\n${description}`
                            : event.title || '';
                    },
                });

                waktuCalendar.render();
            };

            bootWaktuCalendar();
            document.addEventListener('livewire:navigated', bootWaktuCalendar);
        </script>
    @endscript
</x-filament-panels::page>
