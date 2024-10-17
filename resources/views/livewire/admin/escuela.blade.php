<?php

use App\Livewire\Forms\EscuelaForm;
use App\Models\Escuela;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new 
#[Layout('layouts.app')]
class extends Component {
    public EscuelaForm $escuelaForm;
    public string $modelName = 'Escuela';
    public bool $modal = false;
    public bool $modalDelete = false;

    #[On('createEscuela')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    #[On('editEscuela')]
    public function setEscuela(Escuela $escuela)
    {
        $this->resetForm();
        $this->escuelaForm->setEscuela($escuela);
        $this->modal = true;
    }

    #[On('deleteEscuela')]
    public function removeEscuela(Escuela $escuela)
    {
        $this->escuelaForm->setEscuela($escuela);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->escuelaForm->delete();
        $this->dispatch('pg:eventRefresh-EscuelaTable');
        $this->modalDelete = false;
    }

    public function clear()
    {
        $this->resetForm();
    }

    public function save()
    {
        $this->escuelaForm->id
            ? $this->update()
            : $this->store();
    }

    public function validateForm()
    {
        $this->escuelaForm->validate();
    }

    public function store()
    {
        $this->validateForm();
        $this->escuelaForm->store();
        $this->resetForm();
        $this->dispatch('pg:eventRefresh-EscuelaTable');
        $this->modal = false;
    }

    public function update()
    {
        $this->validateForm();
        $this->escuelaForm->update();
        $this->dispatch('pg:eventRefresh-EscuelaTable');
        $this->modal = false;
    }

    public function resetForm()
    {
        $this->escuelaForm->resetValidation();
        $this->escuelaForm->reset();
    }

    public function check()
    {
        if (!Auth::user()->can('mostrar escuelas')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:escuela-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar la escuela?</span>
                <span class="text-center">Recuerda que se eliminarán los registros asociados a esta escuela</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($escuelaForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal"
        width="sm">
        <div class="grid grid-cols-1 gap-3">
            <!-- Nombre -->
            <x-input label="Nombre" placeholder="Ingresar nombre de la escuela" wire:model="escuelaForm.nombre" />
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">
            @if (!$escuelaForm->id)
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