<?php

namespace App\Livewire;

use App\Models\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new
#[Layout('layouts.app')]
class extends Component
{
    public bool $modal = false;
    public $log = null;

    #[On('viewLog')]
    public function setLog(Log $log)
    {
        $this->log = $log;
        $this->modal = true;
    }
}; ?>

<div>
    <livewire:logs-table />
    
    <x-modal wire:model="modal" width="md">
        <x-card title="Detalle del Log">
            @if($log)
            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-2 gap-2">
                    <div class="font-semibold">Acción:</div>
                    <div>{{ $log->action }}</div>
                    
                    <div class="font-semibold">Modelo:</div>
                    <div>{{ $log->model_type }}</div>
                    
                    <div class="font-semibold">ID del Modelo:</div>
                    <div>{{ $log->model_id }}</div>
                    
                    <div class="font-semibold">Usuario:</div>
                    <div>{{ $log->user->name ?? 'N/A' }}</div>
                    
                    <div class="font-semibold">IP:</div>
                    <div>{{ $log->ip_address }}</div>
                    
                    <div class="font-semibold">Fecha:</div>
                    <div>{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                </div>

                @if($log->old_values)
                <div class="mt-4">
                    <div class="font-semibold mb-2">Valores Anteriores:</div>
                    <pre class="bg-gray-100 p-2 rounded">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif

                @if($log->new_values)
                <div class="mt-4">
                    <div class="font-semibold mb-2">Valores Nuevos:</div>
                    <pre class="bg-gray-100 p-2 rounded">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
            </div>
            @endif

            <x-slot name="footer">
                <div class="flex justify-end">
                    <x-button flat label="Cerrar" x-on:click="close" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>
</div>