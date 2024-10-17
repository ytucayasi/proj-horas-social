<?php

use App\Livewire\Forms\Admin\RoleForm;
use App\Models\Escuela;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public RoleForm $roleForm;
    public string $modelName = 'Rol';
    public bool $modal = false;
    public bool $modalDelete = false;
    public bool $modalPermissions = false;
    public $escuelas = [];

    public function mount()
    {
        $this->escuelas = Escuela::all()->map(function ($escuela) {
            return [
                'id' => $escuela->id,
                'name' => $escuela->nombre,
            ];
        });
    }

    /* Abrir modal */
    #[On('createRole')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    /* Obtener Rol */
    public function role()
    {
        return $this->roleForm->getRole();
    }

    /* Setear modelos de Usuario y Persona */
    #[On('editRole')]
    public function setRole(Role $role)
    {
        $this->resetForm();
        $this->roleForm->setRole($role);
        $this->modal = true;
    }

    /* Eliminar usuario */
    #[On('deleteRole')]
    public function removeRole(Role $role)
    {
        $this->roleForm->setRole($role);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->roleForm->delete();
        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-RoleTable');
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
        $this->roleForm->id
            ? $this->update()
            : $this->store();
        /* $this->dispatch('refresh-page'); */
    }

    /* Validación de los campos */
    public function validateForm()
    {
        $this->roleForm->validate();
    }

    /* Regsitrar Usuario General */
    public function store()
    {
        /* Validación deel formulario */
        $this->validateForm();

        /* Registrar Usuario */
        $this->roleForm->store();

        /* Resetear formulario */
        $this->resetForm();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-RoleTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Actualizar Usuario General */
    public function update()
    {
        // Validación del formulario
        $this->validateForm();

        // Actualizar persona
        $this->roleForm->update();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-RoleTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Resetear formulario y validaciones */
    public function resetForm()
    {
        $this->roleForm->resetValidation();
        $this->roleForm->reset();
    }

    /* Abrir modal */
    #[On('showPermissions')]
    public function show(Role $role)
    {
        $this->roleForm->grouped_permissions = [];
        $this->roleForm->setRole($role);
        $this->roleForm->groupPermissions($role->permissions);
        $this->modalPermissions = true;
    }

    public function updated($property, $value)
    {
        if ($property === 'roleForm.action_permission_id') {
            $this->roleForm->selected_permissions = [];
            $this->roleForm->changePermissions($value);
        }
    }
    public function check()
    {
        if (!Auth::user()->can('mostrar roles')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>

<!-- Esto aplica a todos los modulos -->
<div wire:poll="check">
    <livewire:tables.role-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar el rol?</span>
                <span class="text-center">Recuerde que se eliminará definitivamente</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal wire:model="modalPermissions" width="5xl">
        <x-card>
            @if ($roleForm->role)
                <div class="flex space-y-4 flex-col">
                    @if ($roleForm->grouped_permissions)
                        @foreach ($roleForm->grouped_permissions as $groupName => $permissions)
                            <h3 class="uppercase">{{ $groupName }}:</h3> <!-- Encabezado con el nombre del grupo -->
                            <div class="grid grid-cols-4 gap-x-4 gap-y-2 dark:bg-secondary-900 bg-secondary-100 rounded-md p-4">
                                @foreach ($permissions as $permission)
                                    <span>{{ $permission->name }}</span>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="bg-secondary-900 rounded-md p-4">
                            <span>No se añadieron permisos</span>
                        </div>
                    @endif
                </div>
            @endif
        </x-card>
    </x-modal>
    <x-modal-card title="{{($roleForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal" width="sm">
        <div class="grid grid-cols-1 gap-6">

            <!-- Nombre -->
            <x-input label="Nombre" placeholder="Ingresar" wire:model="roleForm.name" class="w-10" />
            <x-select label="Escuela Académica" placeholder="Seleccionar" :options="$this->escuelas" option-label="name"
                option-value="id" wire:model="roleForm.escuela_id" />

            @if ($roleForm->id)
                <div class="flex justify-end">
                    <x-checkbox id="left-label" left-label="¿Desea alterar permisos?"
                        wire:model.live="roleForm.activate_permission" value="left-label" />
                </div>
            @endif
            <!-- Seleccionar Permiso -->
            @if ($roleForm->activate_permission)
                <x-select class="gap-0" hide-empty-message label="Acción" placeholder="Seleccionar" :options="[['name' => 'Agregar', 'id' => 1], ['name' => 'Eliminar', 'id' => 2]]" option-label="name" option-value="id"
                    wire:model.live="roleForm.action_permission_id" />
                <x-select class="gap-0" label="Permiso" placeholder="Seleccionar" :options="$roleForm->permissions"
                    option-label="name" option-value="id" multiselect wire:model.live="roleForm.selected_permissions">
                </x-select>
            @endif
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">

            <!-- Botón de Eliminar -->
            <x-mini-button flat negative rounded icon="trash" wire:click="clear" />
            <div class="flex gap-x-2">

                <!-- Botón de Cancelar -->
                <x-button flat label="Cancelar" x-on:click="close" />

                <!-- Botón de Guardar -->
                <x-button flat positive label="Guardar" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>
    @script
    <script>
        Livewire.on('refresh-page', () => {
            location.reload();
        });
    </script>
    @endscript
</div>