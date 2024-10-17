<div class="mb-3 gap-3 flex flex-wrap justify-between items-center">
    <span class="text-2xl uppercase">{{ $moduleName }}</span>
    @can('crear ' . $moduleName)
        <x-button primary label="Registrar" wire:click="open" icon="plus" />
    @endcan
</div>