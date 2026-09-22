<x-filament-panels::page>
    <x-filament::section>
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
            const boot = () => {
                const el = document.getElementById('waktu-calendar');
                if (!el || typeof FullCalendar === 'undefined') return;
                if (waktuCalendar) waktuCalendar.destroy();
                waktuCalendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    weekNumbers: true,
                    firstDay: 1,
                    height: 700,
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
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
