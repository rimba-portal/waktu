<x-filament-panels::page>
    <x-filament::section>
        <div wire:ignore id="calendar"></div>
    </x-filament::section>

    {{-- Page content --}}
    {{ $this->table }}

    @assets
    <script src="{{ asset('js/rrule.min.js') }}"></script>
    <script src="{{ asset('js/calendar.min.js') }}"></script>
    <script src="{{ asset('js/index.global.min.js') }}"></script>
    @endassets

    @script
    <script>
        let calendar; // keep a reference to avoid duplicate inits

        const calendarFunction = () => {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            // If calendar already exists (e.g. on Livewire navigations), destroy it first
            if (calendar) {
                calendar.destroy();
            }

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                weekNumbers: true,
                firstDay: 1, // Start week on Monday
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'multiMonthYear,dayGridMonth,timeGridWeek'
                },
                height: 600,
                events: JSON.parse($wire.events),

                // ⬇️ Add this: show full title on hover
                eventDidMount: function(info) {
                    // Set the native title attribute for a simple tooltip
                    info.el.setAttribute('title', info.event.title || '');
                },
            });

            calendar.render();
        };

        // Initialize once DOM is ready
        document.addEventListener('DOMContentLoaded', calendarFunction);

        // Re-init when Filament/Livewire navigates
        document.addEventListener('livewire:navigated', calendarFunction);
    </script>
    @endscript
</x-filament-panels::page>