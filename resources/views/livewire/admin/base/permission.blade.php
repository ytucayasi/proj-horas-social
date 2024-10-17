<?php

use App\Livewire\Forms\Admin\PermissionForm;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public PermissionForm $permissionForm;
    public string $modelName = 'Permiso';
    public bool $modal = false;
    public bool $modalDelete = false;

    /* Abrir modal */
    #[On('createPermission')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    /* Obtener Rol */
    public function role()
    {
        return $this->permissionForm->getPermission();
    }

    /* Setear modelos de Usuario y Persona */
    #[On('editPermission')]
    public function setPermission(Permission $permission)
    {
        $this->resetForm();
        $this->permissionForm->setPermission($permission);
        $this->modal = true;
    }

    /* Eliminar usuario */
    #[On('deletePermission')]
    public function removeRole(Permission $permission)
    {
        $this->permissionForm->setPermission($permission);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->permissionForm->delete();
        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-PermissionTable');
        $this->modalDelete = false;
    }

    /* Limpiar formulario */
    public function clear()
    {
        $this->resetForm();
    }

    /* Realizar acción de actualizar o registrar */
    public function save()
    {
        $this->permissionForm->id
            ? $this->update()
            : $this->store();
    }

    /* Validación de los campos */
    public function validateForm()
    {
        $this->permissionForm->validate();
    }

    /* Regsitrar Usuario General */
    public function store()
    {
        /* Validación deel formulario */
        $this->validateForm();

        /* Registrar Usuario */
        $this->permissionForm->store();

        /* Resetear formulario */
        $this->resetForm();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-PermissionTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Actualizar Usuario General */
    public function update()
    {
        // Validación del formulario
        $this->validateForm();

        // Actualizar persona
        $this->permissionForm->update();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-PermissionTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Resetear formulario y validaciones */
    public function resetForm()
    {
        $this->permissionForm->resetValidation();
        $this->permissionForm->reset();
    }
    public function updating($property, $value)
    {
        if ($property === 'permissionForm.action_id' || $property === 'permissionForm.module_id') {
            $actionName = '';
            if ($this->permissionForm->action_id !== null) {
                $action = $this->permissionForm->getAction($this->permissionForm->action_id);
                $actionName = $action ? $action['ab'] : '';
            }
            $moduleName = '';
            if ($this->permissionForm->module_id !== null) {
                $module = $this->permissionForm->getModule($this->permissionForm->module_id);
                $moduleName = $module ? $module['ab'] : '';
            }
            if ($property === 'permissionForm.action_id') {
                $action = $this->permissionForm->getAction($value);
                $actionName = $action ? $action['ab'] : '';
            } elseif ($property === 'permissionForm.module_id') {
                $module = $this->permissionForm->getModule($value);
                $moduleName = $module ? $module['ab'] : '';
            }
            $this->permissionForm->name = trim($actionName . ' ' . $moduleName);
        }
    }
    public function active()
    {
        $this->permissionForm->active();

        /* Resetear formulario */
        $this->resetForm();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-PermissionTable');

        /* Cerrar modal */
        $this->modal = false;
    }
    public function check()
    {
        if (!Auth::user()->can('mostrar permisos')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:tables.permission-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar el permiso?</span>
                <span class="text-center">Recuerde que se eliminará definitivamente</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($permissionForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal"
        width="sm">
        <div class="grid grid-cols-1 gap-4">

            <!-- Action -->
            <x-select class="gap-0" label="Acción" placeholder="Seleccionar" :options="config('admin.actions')"
                option-label="name" option-value="id" wire:model.live="permissionForm.action_id" />

            <!-- Module -->
            <x-select class="gap-0" label="Módulo" placeholder="Seleccionar" :options="config('admin.modules')"
                option-label="name" option-value="id" wire:model.live="permissionForm.module_id" />

            <!-- Nombre -->
            <x-input disabled label="Nombre" placeholder="Ingresar" wire:model="permissionForm.name" />
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">

            <!-- Botón de Eliminar -->
            <x-mini-button flat negative rounded icon="trash" wire:click="clear" />
            <div class="flex gap-x-2">

                <!-- Botón de Cancelar -->
                <x-button flat label="Cancelar" x-on:click="close" />

                <!-- Activar o guardar -->
                @if ($permissionForm->verify_exist_permission())
                    <x-button flat warning label="Activar" wire:click="active" />
                @else
                    <x-button flat positive label="Guardar" wire:click="save" />
                @endif
            </div>
        </x-slot>
    </x-modal-card>
</div>