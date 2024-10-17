<?php

use App\Livewire\Forms\Admin\PersonaForm;
use App\Livewire\Forms\Admin\UserForm;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public UserForm $userForm;
    public PersonaForm $personaForm;
    public string $modelName = 'Usuario';
    public string $areaName = 'default';
    public bool $modal = false;
    public bool $modalDelete = false;
    public $roles = [];

    public function mount()
    {
        $this->roles = Role::whereNotIn('name', ['Super Admin'])->get();
    }

    /* Abrir modal */
    #[On('createUser')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    /* Obtener Usuario */
    public function user()
    {
        return $this->userForm->getUser();
    }

    /* Obtener Persona */
    public function persona()
    {
        return $this->personaForm->getPersona();
    }

    /* Setear modelos de Usuario y Persona */
    #[On('editUser')]
    public function setUser(User $user)
    {
        $this->resetForm();
        $this->userForm->setUser($user);
        $this->personaForm->setPersona($user->id);
        $this->areaName = strtolower($this->getArea($this->personaForm->area_id)['ab']);
        $this->userForm->email = $this->userForm->clearEmail($this->userForm->email);
        $this->userForm->selected_roles = $this->userForm->getRolesIds();
        $this->modal = true;
    }

    /* Eliminar usuario */
    #[On('deleteUser')]
    public function removeUser(User $user)
    {
        $this->userForm->setUser($user);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->userForm->delete();
        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-UserTable');
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
        $this->userForm->id
            ? $this->update()
            : $this->store();
    }

    /* Validación de los campos */
    public function validateForm()
    {
        $this->personaForm->validate();
        $this->userForm->validate();
    }

    /* Regsitrar Usuario General */
    public function store()
    {
        /* Validación deel formulario */
        $this->validateForm();

        /* Registrar Usuario */
        $user = $this->userForm->store($this->areaName);

        $this->userForm->asignRole($user);

        /* Almacenar ID */
        $this->personaForm->id = $user->id;

        /* Registrar Persona */
        $this->personaForm->store();

        /* Resetear formulario */
        $this->resetForm();

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-UserTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Actualizar Usuario General */
    public function update()
    {
        // Validación del formulario
        $this->validateForm();

        // Actualizar persona
        $this->personaForm->update();

        // Actualizar usuario
        $user = $this->userForm->update();

        $this->userForm->removeAllRoles();

        $this->userForm->asignRole($user);

        /* Refrescar la tabla de usuarios */
        $this->dispatch('pg:eventRefresh-UserTable');

        /* Cerrar modal */
        $this->modal = false;
    }

    /* Resetear formulario y validaciones */
    public function resetForm()
    {
        $this->userForm->resetValidation();
        $this->userForm->reset();
        $this->personaForm->resetValidation();
        $this->personaForm->reset();
        $this->areaName = 'default';
    }

    /* Obtener el area */
    public function getArea(?int $id): ?array
    {
        $areas = config('admin.areas');
        foreach ($areas as $area) {
            if ($area['id'] === $id) {
                return $area;
            }
        }
        return null;
    }

    /* Crear nombre de usuario y correo */
    public function createUser(string $nombre = null, string $apellido_paterno = null, int $tipo_campo)
    {
        $nombre = trim(strtolower($nombre ?? ''));
        $apellido_paterno = trim(strtolower($apellido_paterno ?? ''));
        if ($nombre || $apellido_paterno) {
            $variacion = $tipo_campo == 1 ? '.' : ' ';
            return $tipo_campo == 2
                ? (mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8') . $variacion . mb_convert_case($apellido_paterno, MB_CASE_TITLE, 'UTF-8'))
                : (mb_convert_case($nombre, MB_CASE_LOWER, 'UTF-8') . $variacion . mb_convert_case($apellido_paterno, MB_CASE_LOWER, 'UTF-8'));
        } else {
            return '';
        }
    }

    /* Actualizar en tiempo real el correo y nombre de usuario */
    public function updating($property, $value)
    {
        if ($property === 'personaForm.area_id') {
            $this->areaName = $this->getArea($value) ? strtolower($this->getArea($value)['ab']) : 'default';
        } else if ($property === 'personaForm.nombre') {
            $this->userForm->email = $this->createUser($value, $this->personaForm->apellido_paterno, 1);
            $this->userForm->name = $this->createUser($value, $this->personaForm->apellido_paterno, 2);
        } else if ($property === 'personaForm.apellido_paterno') {
            $this->userForm->email = $this->createUser($this->personaForm->nombre, $value, 1);
            $this->userForm->name = $this->createUser($this->personaForm->nombre, $value, 2);
        }
    }

    public function check()
    {
        if (!Auth::user()->can('mostrar usuarios')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:tables.user-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar el usuario?</span>
                <span class="text-center">Recuerda que el usuario no tendrá acceso al sistema</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($userForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal" width="xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- DNI -->
            <x-maskable label="DNI" placeholder="Ingresar" wire:model="personaForm.dni" mask="########" />

            <!-- Nombre -->
            <x-input label="Nombre" placeholder="Ingresar" wire:model.live="personaForm.nombre" />

            <!-- Apellido Paterno -->
            <x-input label="Apellido Paterno" placeholder="Ingresar" wire:model.live="personaForm.apellido_paterno" />

            <!-- Apellido Materno -->
            <x-input label="Apellido Materno" placeholder="Ingresar" wire:model="personaForm.apellido_materno" />
            <!-- Fecha de Nacimiento -->

            <x-datetime-picker only label="Fecha de Nacimiento" placeholder="Seleccionar"
                wire:model="personaForm.fecha_nacimiento" />

            <!-- Género -->
            <x-select label="Género" placeholder="Seleccionar" :options="[['name' => 'Masculino', 'id' => 1], ['name' => 'Femenino', 'id' => 2]]" option-label="name" option-value="id" wire:model="personaForm.genero" />

            <!-- Linea -->
            <hr class="md:col-span-2 my-2 border-slate-300 dark:border-slate-600" />

            <!-- Tipo de Persona -->
            <x-select class="gap-0" label="Tipo de Persona" placeholder="Seleccionar" :options="[['name' => 'Persona Natural', 'id' => 2], ['name' => 'Peronas Jurídica', 'id' => 1],]" option-label="name" option-value="id"
                wire:model="personaForm.tipo_persona" />

            <!-- Area -->
            <x-select class="gap-0" label="Área" placeholder="Seleccionar" :options="config('admin.areas')"
                option-label="name" option-value="id" wire:model.live="personaForm.area_id" />

            <!-- Linea -->
            <hr class="md:col-span-2 my-2 border-slate-300 dark:border-slate-600" />

            <!-- Rol -->
            <x-select multiselect class="gap-0 md:col-span-2" label="Rol" placeholder="Seleccionar"
                :options="$this->roles" option-label="name" option-value="id"
                wire:model.live="userForm.selected_roles" />

            <!-- Usuario -->
            <x-input class="md:col-span-2" disabled label="Usuario" placeholder="Ingresar" wire:model="userForm.name" />

            <!-- Email -->
            <x-input disabled label="Correo" placeholder="Ingresar" suffix="{{'@upeu.edu.pe' }}"
                wire:model="userForm.email" class="md:col-span-2" />

            <!-- Password -->
            <x-password class="md:col-span-2" label="Nueva Contraseña" placeholder="Ingresar"
                wire:model="userForm.password" />
            @if ($userForm->id)
                <x-password class="md:col-span-2" label="Confirmar Contraseña" placeholder="Ingresar"
                    wire:model="userForm.confirm_password" />
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
</div>