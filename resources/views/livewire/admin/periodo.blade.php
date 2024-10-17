<?php

use App\Livewire\Forms\PeriodoForm;
use App\Models\Periodo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new 
#[Layout('layouts.app')]
class extends Component {
    public PeriodoForm $periodoForm;
    public string $modelName = 'Periodo';
    public bool $modal = false;
    public bool $modalDelete = false;

    #[On('createPeriodo')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    #[On('editPeriodo')]
    public function setPeriodo(Periodo $periodo)
    {
        $this->resetForm();
        $this->periodoForm->setPeriodo($periodo);
        $this->modal = true;
    }

    #[On('deletePeriodo')]
    public function removePeriodo(Periodo $periodo)
    {
        $this->periodoForm->setPeriodo($periodo);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->periodoForm->delete();
        $this->dispatch('pg:eventRefresh-PeriodoTable');
        $this->modalDelete = false;
    }

    public function clear()
    {
        $this->resetForm();
    }

    public function save()
    {
        $this->periodoForm->id
            ? $this->update()
            : $this->store();
    }

    public function validateForm()
    {
        $this->periodoForm->validate();
    }

    public function store()
    {
        $this->validateForm();
        $this->periodoForm->store();
        $this->resetForm();
        $this->dispatch('pg:eventRefresh-PeriodoTable');
        $this->modal = false;
    }

    public function update()
    {
        $this->validateForm();
        $this->periodoForm->update();
        $this->dispatch('pg:eventRefresh-PeriodoTable');
        $this->modal = false;
    }

    public function resetForm()
    {
        $this->periodoForm->resetValidation();
        $this->periodoForm->reset();
    }

    public function check()
    {
        if (!Auth::user()->can('mostrar periodos')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:periodo-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar el periodo?</span>
                <span class="text-center">Recuerda que se eliminarán los registros asociados a este periodo</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($periodoForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal"
        width="sm">
        <div class="grid grid-cols-1 gap-3">
            <!-- Nombre -->
            <x-maskable label="Nombre" mask="####-##" placeholder="Ingresar nombre del periodo" wire:model="periodoForm.nombre" />
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">
            @if (!$periodoForm->id)
                <x-mini-button flat negative rounded icon="trash" wire:click="clear" />
            @endif
            <div></div>
            <div class="flex gap-x-2">
                <x-button flat label="Cancelar" x-on:click="close" />
                <x-button flat positive label="Guardar" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>
</div>