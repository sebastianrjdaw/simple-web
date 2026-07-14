<x-filament-widgets::widget>
    <x-filament::section
        heading="Control del reproductor"
        description="Fuerza una recarga completa de la página abierta en la pantalla. La publicación activa no se modifica."
        icon="heroicon-o-arrow-path"
    >
        <x-filament::button
            wire:click="forceReload"
            wire:loading.attr="disabled"
            wire:target="forceReload"
            wire:confirm="¿Forzar la recarga de la página del reproductor?"
            icon="heroicon-o-arrow-path"
        >
            <span wire:loading.remove wire:target="forceReload">Forzar recarga del reproductor</span>
            <span wire:loading wire:target="forceReload">Enviando…</span>
        </x-filament::button>
    </x-filament::section>
</x-filament-widgets::widget>
