<x-filament-panels::page>
    <x-filament::section divided collapsible collapsed x-on:collapse-locations-table.window="isCollapsed = true">
        <x-slot name="heading">Events</x-slot>

        {{ $this->table }}
    </x-filament::section>
    <x-filament::section>
        <x-slot name="heading">{{ $shiftRole ? 'My schedule: '.$shiftRole : 'Company calendar' }}</x-slot>
        <x-slot name="description">
            <div wire:ignore id="waktu-calendar"></div>
        </x-slot>

        <x-slot name="afterHeader">
            <x-filament::fieldset>
                <x-filament::icon-button icon="heroicon-s-sun" color="success" label="Morning" />
                <x-filament::icon-button icon="heroicon-s-sun" color="warning" label="Afternoon" />
                <x-filament::icon-button icon="heroicon-s-sun" color="danger" label="Evening" />
                <x-filament::icon-button icon="heroicon-s-moon" color="gray" label="Night" />
            </x-filament::fieldset>
        </x-slot>
    </x-filament::section>
    @assets
        <script src="{{ asset('js/rrule.min.js') }}"></script>
        <script src="{{ asset('js/calendar.min.js') }}"></script>
        <script src="{{ asset('js/index.global.min.js') }}"></script>
    @endassets
    @script
        <script>
            let waktuCalendar;
            const boot = () => {
                const el = document.getElementById('waktu-calendar');
                if (!el || typeof FullCalendar === 'undefined') return;
                if (waktuCalendar) waktuCalendar.destroy();
                waktuCalendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    weekNumbers: true,
                    firstDay: 1, // Start week on Monday
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'multiMonthYear,dayGridMonth,timeGridWeek',
                    },
                    height: 600,
                    events: @js($events),
                    eventDidMount: (i) => (i.el.title = i.event.title || ''),
                });
                waktuCalendar.render();
            };
            boot();
            document.addEventListener('livewire:navigated', boot);
        </script>
    @endscript
</x-filament-panels::page>
